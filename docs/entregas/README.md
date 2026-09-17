# Entregas a clientes

Aquí vive la documentación de entrega de cada proyecto: qué se entregó, cómo
se accede, qué queda pendiente y quién firma. Nació con la entrega de
MegaHogar (2026-09-05); antes las entregas no dejaban rastro escrito y a los
tres meses nadie recordaba qué se había prometido ni con qué usuario entraba
el cliente.

## Dónde va cada cosa

```
docs/entregas/
  README.md                          ← esta guía
  <slug-del-proyecto>/
    ACTA-ENTREGA-<AAAA-MM-DD>.md     ← se versiona en git
    privado/                          ← IGNORADO por git (.gitignore)
      CREDENCIALES.md                 ← usuarios y contraseñas entregadas
```

**El acta se versiona. Las contraseñas no.** El acta lista los usuarios y
sus roles, y dice que las claves se entregaron por canal separado. Las claves
se guardan en `privado/`, que git ignora, y se cambian en el primer ingreso
del cliente.

## Regla sobre credenciales

1. La fuente principal es el **gestor de contraseñas de Eskala** (bóveda
   compartida de clientes). `privado/` es solo la copia de trabajo local.
2. Ningún archivo versionado contiene una contraseña, un token ni una clave
   de API. Si aparece una en un commit, se rota ese mismo día.
3. Las claves se entregan al cliente por un canal distinto del acta
   (WhatsApp al gerente, o impreso en la reunión), nunca pegadas en el
   mismo correo que el PDF.
4. Cada usuario del cliente debe tener **correo propio** cargado en el panel.
   Sin correo no hay recuperación de contraseña, y ese es el primer soporte
   que va a llegar.

## Qué debe contener un acta

- Datos del cliente y del proyecto (razón social, RUC, contacto).
- Qué se entrega, en concreto: URLs públicas, panel, módulos, bot.
- Estado verificado el día de la entrega, con fecha: cifras del catálogo,
  páginas que responden, dominio y certificado, estado del bot.
- Accesos: usuario y rol de cada persona. Sin contraseñas.
- Pendientes conocidos, con responsable (cliente o Eskala).
- Cómo pedir soporte y qué cubre.
- Firmas.

Se redacta desde datos comprobados ese día en producción, no desde memoria.
Si algo no se pudo comprobar, se dice.

## Cómo generar el PDF para el cliente

El acta en Markdown es la fuente. El PDF que se entrega se genera desde ella
(`/make-pdf` sobre el archivo del acta) y no se edita a mano, para que
cualquier corrección quede en el Markdown versionado.
