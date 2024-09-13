import React from "react";
import "./grafico.css";
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  ReferenceLine,
} from "recharts";

type Datos = {
  id: number;
  temperature: number;
  recorded_at: string;
};

type GraficoProps = {
  datos: Datos[];
  alto: number;
  alertaMinima: number;
  alertaMaxima: number;
  mostrarAlertas?: boolean;
};

const CustomTooltip = ({ payload, label }: any) => {
  if (!payload || payload.length === 0) return null;

  const { value, name } = payload[0];

  return (
    <div className="custom-tooltip">
      <p>{label}</p>
      {name === "temperature" && (
        <p>Temperatura: {value}°C</p>
      )}
    </div>
  );
};

const Grafico: React.FC<GraficoProps> = ({
  datos,
  alto,
  alertaMinima,
  alertaMaxima,
  mostrarAlertas = false,
}) => {
  return (
    <>
      <ResponsiveContainer width="100%" height={alto}>
        <AreaChart data={datos}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="recorded_at" />
          <YAxis
            domain={[
              (dataMin: number) => Math.min(dataMin - 1, alertaMinima - 0.5),
              (dataMax: number) => Math.max(dataMax + 1, alertaMaxima + 0.5)
            ]}
          />
          <Tooltip content={<CustomTooltip />} />
          <Area
            type="monotone"
            dataKey="temperature"
            stroke="#8884d8"
            fillOpacity={0.3}
            fill="#8884d8"
          />
          {mostrarAlertas && (
            <>
              <ReferenceLine y={alertaMinima} label={`Alerta Mínima: ${alertaMinima}°C`} stroke="#F9A825" strokeDasharray="3 3" />
              <ReferenceLine y={alertaMaxima} label={`Alerta Máxima: ${alertaMaxima}°C`} stroke="#C62828" strokeDasharray="3 3" />
            </>
          )}
        </AreaChart>
      </ResponsiveContainer>
    </>
  );
};

export default Grafico;
