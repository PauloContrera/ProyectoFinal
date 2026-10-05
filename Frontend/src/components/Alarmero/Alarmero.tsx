import { useCallback, useEffect, useMemo, useState } from "react";
import "./Alarmero.css";
import { api } from "../../services/api";
import { DeviceAlert, AlertStatusFilter } from "../../types";
import { useAuth } from "../../hooks/useAuth";

const FILTROS: { key: AlertStatusFilter; label: string }[] = [
  { key: "open", label: "Sin resolver" },
  { key: "active", label: "Activas" },
  { key: "acknowledged", label: "Reconocidas" },
  { key: "resolved", label: "Resueltas" },
  { key: "all", label: "Todas" },
];

const formatearFecha = (valor: string): string => {
  const fecha = new Date(valor.includes("T") ? valor : valor.replace(" ", "T") + "Z");
  return Number.isNaN(fecha.getTime()) ? valor : fecha.toLocaleString();
};

type EstadoAlarma = "activa" | "reconocida" | "resuelta";

// Maquina de estados del backend: Activa -> Reconocida -> Resuelta.
const estadoDeAlerta = (a: DeviceAlert): EstadoAlarma => {
  if (Number(a.resolved) === 1) return "resuelta";
  if (Number(a.acknowledged) === 1) return "reconocida";
  return "activa";
};

const ETIQUETA_ESTADO: Record<EstadoAlarma, string> = {
  activa: "Activa",
  reconocida: "Reconocida",
  resuelta: "Resuelta",
};

export default function Alarmero() {
  const { user } = useAuth();
  const puedeAccionar = Boolean(user && user.role !== "visitor");

  const [filtro, setFiltro] = useState<AlertStatusFilter>("open");
  const [alertas, setAlertas] = useState<DeviceAlert[]>([]);
  const [cargando, setCargando] = useState(false);
  const [error, setError] = useState("");
  const [accionandoId, setAccionandoId] = useState<number | null>(null);

  const cargar = useCallback(async () => {
    setCargando(true);
    setError("");
    try {
      const res = await api.get<DeviceAlert[]>(`/alerts?status=${filtro}&limit=300`);
      setAlertas(Array.isArray(res.data) ? res.data : []);
    } catch {
      setAlertas([]);
      setError("No se pudieron cargar las alarmas.");
    } finally {
      setCargando(false);
    }
  }, [filtro]);

  useEffect(() => {
    cargar();
  }, [cargar]);

  const accionar = async (id: number, accion: "acknowledge" | "resolve") => {
    setAccionandoId(id);
    try {
      await api.put(`/alerts/${id}/${accion}`, {});
      // Refresco para que el filtro actual quede consistente.
      await cargar();
    } catch {
      setError(
        accion === "acknowledge"
          ? "No se pudo reconocer la alarma."
          : "No se pudo resolver la alarma."
      );
    } finally {
      setAccionandoId(null);
    }
  };

  const resumen = useMemo(() => {
    let activas = 0;
    let reconocidas = 0;
    for (const a of alertas) {
      const estado = estadoDeAlerta(a);
      if (estado === "activa") activas++;
      else if (estado === "reconocida") reconocidas++;
    }
    return { activas, reconocidas, total: alertas.length };
  }, [alertas]);

  return (
    <div className="AlarmeroTotal">
      <div className="AlarmeroCabecera">
        <h2 className="AlarmeroTitulo">Alarmas</h2>
        <div className="AlarmeroResumen">
          <span className="AlarmeroResumenItem activa">{resumen.activas} sin atender</span>
          {resumen.reconocidas > 0 && (
            <span className="AlarmeroResumenItem reconocida">
              {resumen.reconocidas} reconocidas
            </span>
          )}
        </div>
      </div>

      <div className="AlarmeroFiltros">
        {FILTROS.map((f) => (
          <button
            key={f.key}
            type="button"
            className={`AlarmeroFiltro ${filtro === f.key ? "activo" : ""}`}
            aria-pressed={filtro === f.key}
            onClick={() => setFiltro(f.key)}
          >
            {f.label}
          </button>
        ))}
      </div>

      {cargando && <p className="AlarmeroEstado">Cargando alarmas...</p>}
      {!cargando && error && <p className="AlarmeroEstado">{error}</p>}
      {!cargando && !error && alertas.length === 0 && (
        <p className="AlarmeroEstado">No hay alarmas para este filtro.</p>
      )}

      {!cargando && alertas.length > 0 && (
        <div className="AlarmeroTablaWrap">
          <table className="AlarmeroTabla">
            <thead>
              <tr>
                <th>Heladera</th>
                <th>Tipo</th>
                <th>Temp.</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {alertas.map((a) => {
                const estado = estadoDeAlerta(a);
                const esAlta = a.type === "TEMP_HIGH";
                const enCurso = accionandoId === a.id;
                return (
                  <tr key={a.id} className={`AlarmaFila estado-${estado}`}>
                    <td>
                      <div className="AlarmaHeladera">{a.device_name || `Heladera #${a.device_id}`}</div>
                      {a.device_location && <div className="AlarmaUbicacion">{a.device_location}</div>}
                    </td>
                    <td>
                      <span className={`AlarmaTipo ${esAlta ? "alta" : "baja"}`}>
                        {esAlta ? "Temp. alta" : "Temp. baja"}
                      </span>
                    </td>
                    <td className="AlarmaTemp">
                      {a.temperature !== null ? `${Number(a.temperature).toFixed(1)}°C` : "—"}
                    </td>
                    <td className="AlarmaFecha">{formatearFecha(a.recorded_at)}</td>
                    <td>
                      <span className={`AlarmaEstadoBadge ${estado}`}>{ETIQUETA_ESTADO[estado]}</span>
                    </td>
                    <td className="AlarmaAcciones">
                      {puedeAccionar && estado !== "resuelta" ? (
                        <>
                          {estado === "activa" && (
                            <button
                              type="button"
                              className="AlarmaBtn reconocer"
                              disabled={enCurso}
                              onClick={() => accionar(a.id, "acknowledge")}
                            >
                              {enCurso ? "..." : "Reconocer"}
                            </button>
                          )}
                          <button
                            type="button"
                            className="AlarmaBtn resolver"
                            disabled={enCurso}
                            onClick={() => accionar(a.id, "resolve")}
                          >
                            {enCurso ? "..." : "Resolver"}
                          </button>
                        </>
                      ) : (
                        <span className="AlarmaSinAccion">—</span>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
