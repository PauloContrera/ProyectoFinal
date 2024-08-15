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

function Grafico({ datos, alto , variableAmarrilla, variableRoja, mostrarVariable=false}) {

  var AlertaRoja="Alerta Roja : " ;
  var AlertaAmarrilla="Alerta Amarilla : ";
  // "temperatura": 12.0

  return (
    <>
      <ResponsiveContainer width="100%" height={alto}>
        <AreaChart data={datos}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="hora" />
          <YAxis domain={["dataMin ", "dataMax "]} />
          <Tooltip />
          <Area
            type="monotone"
            dataKey="temperatura"
            stroke="#8884d8"
            fillOpacity={0.1}
            fill="#8884d8"
          />
          {mostrarVariable && [
  <Area
    key="alertaRoja"
    type="monotone"
    dataKey={() => AlertaRoja}
    strokeOpacity={0.6}
    fillOpacity={0}
    stroke="#C62828 "
  />,
  <Area
    key="variableRoja"
    type="monotone"
    dataKey={() => variableRoja}
    strokeOpacity={0.6}
    fillOpacity={0}
    stroke="#C62828 "
  />,
  <Area
    key="alertaAmarilla"
    type="monotone"
    dataKey={() => AlertaAmarrilla}
    strokeOpacity={0.6}
    fillOpacity={0}
    stroke="#F9A825  "
  />,
  <Area
    key="variableAmarilla"
    type="monotone"
    dataKey={() => variableAmarrilla}
    strokeOpacity={0.6}
    fillOpacity={0}
    stroke="#F9A825"
  />
]}

        </AreaChart>
      </ResponsiveContainer>
    
    </>
  );
}

export default Grafico;
