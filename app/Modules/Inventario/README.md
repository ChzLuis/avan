# Inventario

**Qué va aquí:** InventoryMovement, InventoryLedger (escritor único del stock), Proveedor, ImportLog, compras futuras.

**Qué NO va aquí:** Escrituras directas a products.stock desde otro módulo: todo pasa por el ledger.

**Orden en la mudanza:** 3.º — el escritor único ya está definido.

**Estado:** vacío. El código sigue en su sitio actual hasta que le toque, según `docs/architecture/BIXO_MODULARIZACION_PLAN.md`.
