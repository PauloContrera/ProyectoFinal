export interface LastTemperature {
    id: number;
    temperature: number;
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
    min_temp: number;
    max_temp: number;
    group: Group;
    last_temperature: LastTemperature;
    last_alert: LastAlert | null;
  }
  
  export type GroupData = Refrigerator[];
  