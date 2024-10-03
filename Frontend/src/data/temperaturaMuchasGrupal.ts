const GruposTemperatura = [
  [
    {
      id: 1,
      name: "Medicamentos",
      location: "Almacén de Medicamentos",
      min_temp: 2.0,
      max_temp: 8.0,
      group: {
        id: 1,
        name: "Grupo Hospital Central",
        description: "Heladeras para almacenamiento de medicamentos en el Hospital Central",
      },
      last_temperature: {
        id: 33,
        temperature: 8.0,
        recorded_at: "2024-08-20 18:27:10",
      },
      last_alert: {
        id: 3,
        temperature_record_id: 31,
        alert_type: "Excede el límite",
        created_at: "2024-08-19 11:32:26",
      },
    },
    {
      id: 2,
      name: "Vacunas",
      location: "Área de Vacunación",
      min_temp: 3.0,
      max_temp: 7.0,
      group: {
        id: 1,
        name: "Grupo Hospital Central",
        description: "Heladeras para vacunas en el Hospital Central",
      },
      last_temperature: {
        id: 20,
        temperature: 2.5,
        recorded_at: "2024-08-15 11:42:49",
      },
      last_alert: null,
    },
  ],
  [
    {
      id: 3,
      name: "Medicamentos",
      location: "Farmacia",
      min_temp: 0.0,
      max_temp: 5.0,
      group: {
        id: 2,
        name: "Grupo Centro de Salud Norte",
        description: "Heladeras para almacenamiento de medicamentos en el Centro de Salud Norte",
      },
      last_temperature: {
        id: 35,
        temperature: 4.0,
        recorded_at: "2024-08-22 10:12:00",
      },
      last_alert: null,
    },
    {
      id: 4,
      name: "Vacunas",
      location: "Área de Vacunación",
      min_temp: 3.0,
      max_temp: 9.0,
      group: {
        id: 2,
        name: "Grupo Centro de Salud Norte",
        description: "Heladeras para vacunas en el Centro de Salud Norte",
      },
      last_temperature: {
        id: 36,
        temperature: 7.0,
        recorded_at: "2024-08-23 14:55:30",
      },
      last_alert: {
        id: 4,
        temperature_record_id: 35,
        alert_type: "Excede el límite",
        created_at: "2024-08-23 15:00:00",
      },
    },
  ],
  [
    {
      id: 5,
      name: "Medicamentos",
      location: "Almacén de Medicamentos",
      min_temp: 1.00,
      max_temp: 6.00,
      group: {
        id: 3,
        name: "Grupo Vacunatorio Municipal",
        description: "Heladeras de almacenamiento en el Vacunatorio Municipal",
      },
      last_temperature: {
        id: 40,
        temperature: 5.50,
        recorded_at: "2024-08-24 09:18:45",
      },
      last_alert: null,
    },
    {
      id: 6,
      name: "Vacunas",
      location: "Área de Vacunación",
      min_temp: 2.50,
      max_temp: 7.50,
      group: {
        id: 3,
        name: "Grupo Vacunatorio Municipal",
        description: "Heladeras para vacunas en el Vacunatorio Municipal",
      },
      last_temperature: {
        id: 42,
        temperature: 7.20,
        recorded_at: "2024-08-24 12:00:00",
      },
      last_alert: null,
    },
    {
      id: 7,
      name: "Insulina",
      location: "Área de Almacenamiento Especial",
      min_temp: 1.50,
      max_temp: 6.50,
      group: {
        id: 3,
        name: "Grupo Vacunatorio Municipal",
        description: "Heladeras para almacenamiento de insulina en el Vacunatorio Municipal",
      },
      last_temperature: {
        id: 44,
        temperature: 6.00,
        recorded_at: "2024-08-25 08:00:00",
      },
      last_alert: null,
    },
  ],
];

export default GruposTemperatura;
