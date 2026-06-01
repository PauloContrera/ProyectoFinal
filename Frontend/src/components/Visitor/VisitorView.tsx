import { useCallback, useEffect, useMemo, useState } from "react";
import { Eye, Package, RefreshCw, Thermometer } from "lucide-react";
import { api } from "../../services/api";
import { Device, StockItem } from "../../types";
import "./VisitorView.css";

type StockByDevice = Record<number, StockItem[]>;

const formatDate = (value?: string | null) => {
  if (!value) return "-";
  const date = new Date(value.replace(" ", "T"));
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
};

const readError = (error: unknown, fallback: string) =>
  error instanceof Error ? error.message : fallback;

export default function VisitorView() {
  const [devices, setDevices] = useState<Device[]>([]);
  const [stockByDevice, setStockByDevice] = useState<StockByDevice>({});
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  const loadVisitorData = useCallback(async () => {
    setIsLoading(true);
    setError("");

    try {
      const devicesResponse = await api.get<Device[]>("/devices");
      const nextDevices = Array.isArray(devicesResponse.data) ? devicesResponse.data : [];
      const stockEntries = await Promise.all(
        nextDevices.map(async (device) => {
          const stockResponse = await api.get<StockItem[]>(`/devices/${device.id}/stock`);
          return [device.id, Array.isArray(stockResponse.data) ? stockResponse.data : []] as const;
        })
      );

      setDevices(nextDevices);
      setStockByDevice(Object.fromEntries(stockEntries));
    } catch (err: unknown) {
      setDevices([]);
      setStockByDevice({});
      setError(readError(err, "No se pudieron cargar tus accesos"));
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadVisitorData();
  }, [loadVisitorData]);

  const stockTotal = useMemo(
    () => Object.values(stockByDevice).reduce((total, items) => total + items.length, 0),
    [stockByDevice]
  );

  return (
    <section className="VisitorView">
      <div className="VisitorHeader">
        <div>
          <h2>Vista visitante</h2>
          <p>Acceso de lectura para monitoreo de heladeras, temperaturas y stock.</p>
        </div>
        <button type="button" onClick={loadVisitorData} disabled={isLoading}>
          <RefreshCw size={16} />
          Actualizar
        </button>
      </div>

      <div className="VisitorMetrics">
        <div>
          <Eye size={18} />
          <span>{devices.length}</span>
          <p>heladeras visibles</p>
        </div>
        <div>
          <Package size={18} />
          <span>{stockTotal}</span>
          <p>items de stock</p>
        </div>
        <div>
          <Thermometer size={18} />
          <span>Solo lectura</span>
          <p>sin permisos de edicion</p>
        </div>
      </div>

      {isLoading && <p className="VisitorMuted">Cargando accesos...</p>}
      {error && <p className="VisitorError">{error}</p>}

      {!isLoading && !error && (
        <div className="VisitorGrid">
          {devices.map((device) => {
            const stock = stockByDevice[device.id] || [];
            return (
              <article className="VisitorDevice" key={device.id}>
                <div className="VisitorDeviceHeader">
                  <div>
                    <h3>{device.name}</h3>
                    <p>{device.location || "Sin ubicacion"} | {device.device_code}</p>
                  </div>
                  <span>{device.min_temp} / {device.max_temp} C</span>
                </div>

                <div className="VisitorTemp">
                  <Thermometer size={18} />
                  <strong>{device.last_temperature ?? "-"} C</strong>
                  <span>{formatDate(device.last_temperature_recorded_at || device.last_reported_at)}</span>
                </div>

                <div className="VisitorStock">
                  <h4>Stock visible</h4>
                  {stock.length > 0 ? (
                    <ul>
                      {stock.slice(0, 5).map((item) => (
                        <li key={item.id}>
                          <span>{item.name}</span>
                          <strong>{item.quantity}</strong>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <p>No hay stock cargado.</p>
                  )}
                </div>
              </article>
            );
          })}
        </div>
      )}

      {!isLoading && !error && devices.length === 0 && (
        <p className="VisitorMuted">Todavia no tenes heladeras compartidas.</p>
      )}
    </section>
  );
}
