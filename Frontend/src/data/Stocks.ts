const GruposStocks = [
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
      stock: [
        { id: 1, name: "Paracetamol", quantity: 100, expirationDate: "2024-10-31" },
        { id: 2, name: "Amoxicilina", quantity: 50, expirationDate: "2025-01-15" }
      ]
    },
    {
      id: 2,
      name: "Vacunas",
      location: "Área de Vacunación",
      min_temp: 1.0,
      max_temp: 7.0,
      group: {
        id: 1,
        name: "Grupo Hospital Central",
        description: "Heladeras para vacunas en el Hospital Central",
      },
      stock: [
        { id: 3, name: "Vacuna COVID-19", quantity: 100, expirationDate: "2024-03-31" },
        { id: 4, name: "Vacuna Gripe", quantity: 150, expirationDate: "2024-06-30" }
      ]
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
      stock: [
        { id: 5, name: "Ibuprofeno", quantity: 200, expirationDate: "2024-12-31" },
        { id: 6, name: "Aspirina", quantity: 100, expirationDate: "2024-09-30" }
      ]
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
      stock: [
        { id: 7, name: "Vacuna Hepatitis B", quantity: 80, expirationDate: "2024-05-31" },
        { id: 8, name: "Vacuna Tétanos", quantity: 60, expirationDate: "2024-04-15" }
      ]
    },
  ],
  [
    {
      id: 5,
      name: "Medicamentos",
      location: "Almacén de Medicamentos",
      min_temp: 1.0,
      max_temp: 6.0,
      group: {
        id: 3,
        name: "Grupo Vacunatorio Municipal",
        description: "Heladeras de almacenamiento en el Vacunatorio Municipal",
      },
      stock: [
        { id: 9, name: "Metformina", quantity: 120, expirationDate: "2025-02-28" },
        { id: 10, name: "Losartán", quantity: 80, expirationDate: "2024-12-31" }
      ]
    },
    {
      id: 6,
      name: "Vacunas",
      location: "Área de Vacunación",
      min_temp: 2.5,
      max_temp: 7.5,
      group: {
        id: 3,
        name: "Grupo Vacunatorio Municipal",
        description: "Heladeras para vacunas en el Vacunatorio Municipal",
      },
      stock: [
        { id: 11, name: "Vacuna Gripe", quantity: 200, expirationDate: "2024-06-30" },
        { id: 12, name: "Vacuna COVID-19", quantity: 150, expirationDate: "2024-09-30" }
      ]
    },
    {
      id: 7,
      name: "Insulina",
      location: "Área de Almacenamiento Especial",
      min_temp: 1.5,
      max_temp: 6.5,
      group: {
        id: 3,
        name: "Grupo Vacunatorio Municipal",
        description: "Heladeras para almacenamiento de insulina en el Vacunatorio Municipal",
      },
      stock: [
        { id: 13, name: "Insulina", quantity: 100, expirationDate: "2024-08-31" }
      ]
    },
  ],
];

export default GruposStocks;
