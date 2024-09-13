export interface Item {
    id: number;
    name: string;
    quantity: number;
    unit: string;
    expirationDate: string;
  }
  
  export interface Refrigerator {
    id: number;
    name: string;
    location: string;
    stock: Item[];
    // Otros campos relacionados a la temperatura han sido eliminados
  }
  
  export interface Group {
    id: number;
    name: string;
    description: string;
  }
  
  export interface GroupData {
    id: number;
    name: string;
    location: string;
    group: Group;
    stock: Item[];
  }
  