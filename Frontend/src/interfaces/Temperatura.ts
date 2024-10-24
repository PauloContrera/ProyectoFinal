export interface LastTemperature {
    id: number;
    temperature: string;
    recorded_at: string;
  }
  
  export interface LastAlert {
    id: number;
    temperature_record_id: number;
    alert_type: string;
    created_at: string;
  }
  
  export interface Group {
    id: number;
    name: string;
    description: string;
  }
  
  export interface Refrigerator {
    id: number;
    name: string;
    location: string;
    min_temp: number;  // Cambiar a number
    max_temp: number;  // Cambiar a number
    last_temperature: {
      id: number;
      temperature: number; // Asegúrate de que sea number
      recorded_at: string;
    };
    group?: { name: string; description: string }; // Agregar el grupo si es parte del refrigerador

  }
  
  export type GroupData = Refrigerator[];
  