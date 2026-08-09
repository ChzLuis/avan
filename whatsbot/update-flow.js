// update-flow.js - Actualizar flujo del bot por comando
const fs = require('fs');
const path = require('path');

const BOT_TYPE = process.argv[2] || 'rifa';
const FLOW_FILE = path.join(__dirname, `flow-${BOT_TYPE}.json`);

// Estados nuevos para validación de datos
const nuevosEstados = {
    "esperando_nombre": {
        "key": "esperando_nombre",
        "message": "📝 *Paso 1/3*\n\nEscribe tu *nombre completo*:",
        "input_type": "text",
        "validation_error": "⚠️ El nombre debe tener al menos 2 caracteres y solo letras.",
        "transitions": [
            {
                "trigger": "*",
                "action": "validar_nombre",
                "to": "esperando_dni"
            }
        ]
    },
    "esperando_dni": {
        "key": "esperando_dni",
        "message": "📝 *Paso 2/3*\n\nEscribe tu *DNI* (8 dígitos):",
        "input_type": "text",
        "validation_error": "⚠️ El DNI debe tener exactamente 8 dígitos numéricos.",
        "transitions": [
            {
                "trigger": "*",
                "action": "validar_dni",
                "to": "esperando_telefono"
            }
        ]
    },
    "esperando_telefono": {
        "key": "esperando_telefono",
        "message": "📝 *Paso 3/3*\n\nEscribe tu *número de celular* (9 dígitos, empieza con 9):",
        "input_type": "text",
        "validation_error": "⚠️ El celular debe tener 9 dígitos y empezar con 9.",
        "transitions": [
            {
                "trigger": "*",
                "action": "validar_telefono",
                "to": "confirmar_datos"
            }
        ]
    },
    "confirmar_datos": {
        "key": "confirmar_datos",
        "message": "✅ *CONFIRMA TUS DATOS*\n\n👤 *Nombre:* {nombre}\n🆔 *DNI:* {dni}\n📱 *Celular:* {telefono}\n\n¿Son correctos?\n\n1️⃣ *Sí, continuar*\n2️⃣ *No, corregir*",
        "input_type": "option",
        "transitions": [
            {
                "trigger": "1",
                "action": "confirmar_datos_correctos",
                "to": "siguiente"
            },
            {
                "trigger": "2",
                "action": "reiniciar_datos",
                "to": "esperando_nombre"
            }
        ]
    }
};

// Leer archivo actual
if (fs.existsSync(FLOW_FILE)) {
    const flow = JSON.parse(fs.readFileSync(FLOW_FILE, 'utf8'));
    
    // Agregar nuevos estados
    flow.states = { ...flow.states, ...nuevosEstados };
    
    // Guardar
    fs.writeFileSync(FLOW_FILE, JSON.stringify(flow, null, 2));
    console.log(`✅ Estados agregados a ${FLOW_FILE}`);
    console.log('📋 Estados agregados:', Object.keys(nuevosEstados).join(', '));
} else {
    console.log('❌ Archivo no encontrado. Creando uno nuevo...');
    const newFlow = {
        flow_id: 1,
        bot_type: BOT_TYPE,
        project_id: 1,
        states: {
            inicio: {
                key: "inicio",
                message: "🎰 *Bienvenido!*\n\nEscribe *MENU* para ver las opciones.",
                input_type: "text",
                transitions: []
            },
            ...nuevosEstados
        }
    };
    fs.writeFileSync(FLOW_FILE, JSON.stringify(newFlow, null, 2));
    console.log(`✅ Archivo creado: ${FLOW_FILE}`);
}
