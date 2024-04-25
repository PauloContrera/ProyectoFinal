import "./grafico.css";
import {
  LineChart,
  AreaChart,
  Line,
  Legend,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

function Grafico({ datos, alto = 400, variableAmarrilla, variableRoja}) {

  var AlertaRoja="Alerta Roja : ";
  var AlertaAmarrilla="Alerta Amarilla : ";
  // "temperatura": 12.0
  return (
    <>
      <ResponsiveContainer width="100%" height={alto}>
        <AreaChart data={datos}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="timestamp" />
          <YAxis domain={["dataMin - 1", "dataMax + 1"]} />
          <Tooltip />
          <Area
            type="monotone"
            dataKey="temperatura"
            stroke="#8884d8"
            fillOpacity={0.1}
            fill="#8884d8"
          />
          <Area
            type="monotone"
            dataKey={() => AlertaRoja}
            strokeOpacity={0.6}
            fillOpacity={0}
            stroke="red"
          />
          <Area
            type="monotone"
            dataKey={() => variableRoja}
            strokeOpacity={0.6}
            fillOpacity={0}
            stroke="red"
          />
          <Area
            type="monotone"
            dataKey={() => AlertaAmarrilla}
            strokeOpacity={0.6}
            fillOpacity={0}
            stroke="yellow"
          />
           <Area
            type="monotone"
            dataKey={() => variableAmarrilla}
            strokeOpacity={0.6}
            fillOpacity={0}
            stroke="yellow"
          />
        </AreaChart>
      </ResponsiveContainer>
    
    </>
  );
}

export default Grafico;
