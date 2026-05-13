// db.js - Inicialización de datos
const initialData = {
    productos: [
        { id: 1, nombre: "Aceite 20W50", categoria: "Mecánica", precio: 15.00, stock: 12, min: 5 },
        { id: 2, nombre: "Pastillas de Freno (Civic)", categoria: "Mecánica", precio: 25.00, stock: 3, min: 5 },
        { id: 3, nombre: "Bombillo H4 Neón", categoria: "Electricidad", precio: 8.50, stock: 0, min: 10 }
    ],
    facturas: [],
    config: {
        taller: "Multiservicios Sabor Natural",
        moneda: "$",
        iva: 16
    }
};

if (!localStorage.getItem('taller_db')) {
    localStorage.setItem('taller_db', JSON.stringify(initialData));
}