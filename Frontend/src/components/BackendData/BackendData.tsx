import { useEffect, useState } from "react";
import { api } from "../../services/api";
import { Device, DeviceGroup } from "../../types";
import { useAuth } from "../../hooks/useAuth";
import "./BackendData.css";

export default function BackendData() {
  const { isAuthenticated, user } = useAuth();
  const [devices, setDevices] = useState<Device[]>([]);
  const [groups, setGroups] = useState<DeviceGroup[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!isAuthenticated) {
      setDevices([]);
      setGroups([]);
      return;
    }

    let isMounted = true;

    const loadBackendData = async () => {
      setIsLoading(true);
      setError("");

      try {
        const [devicesResponse, groupsResponse] = await Promise.all([
          api.get<Device[]>("/devices"),
          api.get<DeviceGroup[]>("/device-groups"),
        ]);

        if (!isMounted) return;

        setDevices(Array.isArray(devicesResponse.data) ? devicesResponse.data : []);
        setGroups(Array.isArray(groupsResponse.data) ? groupsResponse.data : []);
      } catch (err: any) {
        if (!isMounted) return;
        setError(err.message || "No se pudieron cargar los datos del backend");
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    };

    loadBackendData();

    return () => {
      isMounted = false;
    };
  }, [isAuthenticated]);

  if (!isAuthenticated) {
    return (
      <section className="BackendData">
        <h2 className="BackendData-title">Datos del backend</h2>
        <p className="BackendData-empty">Inicia sesion para cargar datos reales.</p>
      </section>
    );
  }

  return (
    <section className="BackendData">
      <div className="BackendData-header">
        <div>
          <h2 className="BackendData-title">Datos del backend</h2>
          <p className="BackendData-subtitle">
            Sesion activa: {user?.name || user?.username || user?.email}
          </p>
        </div>
      </div>

      {isLoading && <p className="BackendData-empty">Cargando datos...</p>}
      {error && <p className="BackendData-error">{error}</p>}

      {!isLoading && !error && (
        <div className="BackendData-grid">
          <div className="BackendData-panel">
            <h3>Heladeras</h3>
            {devices.length > 0 ? (
              <table>
                <thead>
                  <tr>
                    <th>Codigo</th>
                    <th>Nombre</th>
                    <th>Ubicacion</th>
                    <th>Rango</th>
                  </tr>
                </thead>
                <tbody>
                  {devices.map((device) => (
                    <tr key={device.id}>
                      <td>{device.device_code}</td>
                      <td>{device.name}</td>
                      <td>{device.location || "-"}</td>
                      <td>
                        {device.min_temp} / {device.max_temp}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p className="BackendData-empty">No hay heladeras para mostrar.</p>
            )}
          </div>

          <div className="BackendData-panel">
            <h3>Grupos</h3>
            {groups.length > 0 ? (
              <table>
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                  </tr>
                </thead>
                <tbody>
                  {groups.map((group) => (
                    <tr key={group.id}>
                      <td>{group.name}</td>
                      <td>{group.description || "-"}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p className="BackendData-empty">No hay grupos para mostrar.</p>
            )}
          </div>
        </div>
      )}
    </section>
  );
}
