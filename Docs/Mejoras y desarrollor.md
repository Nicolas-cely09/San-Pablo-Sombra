# DESARROLLOS Y MEJORAS - PROYECTO SAN PABLO

## 1. MÓDULO PACIENTES

### 1.1. Vista / Formulario "Crear Paciente"
* **Apertura Individual:** Al seleccionar la opción "Crear Paciente", la vista debe abrirse individualmente dentro del iframe.
* **Clasificación por Tipo de Discapacidad:** Añadir un campo de selección (`select`) con las siguientes opciones:
  * Intelectual
  * Sensorial
  * Física
  * Psicosocial
  * Múltiple
  * Otro (incluir cuadro de texto para especificar)
* **Asignación de Profesional:**
  * Campo tipo `select` obligatorio que cargue el listado de profesionales registrados en el sistema.
* **Carga de Documentos Adjuntos:** Botón/módulo para subir los siguientes archivos del paciente:
  * Historia clínica
  * Diagnósticos anteriores
  * Consentimientos
  * Fotografía del paciente
  * Informe de objetivos y tratamiento a realizar

---

### 1.2. Vista "Listado de Pacientes" (Admin y Profesional)
*Nota: Para el rol Profesional, esta vista se denomina "Pacientes Asignados".*

* **Ajustes de Interfaz:**
  * Eliminar de esta vista los botones "Modificar Paciente" y "Asignar Paciente".
* **Columnas de la Tabla:**
  1. Documento
  2. Nombre
  3. Edad
  4. Colegio
  5. Acudiente
  6. Contacto
  7. Estado (*Único campo editable directamente al hacer clic en él*)
  8. Profesional

---

### 1.3. Vista Individual / Detalle del Paciente (Apertura en Iframe)
* **Acceso:** Se abre en el iframe al hacer clic sobre un paciente en el listado.
* **Diseño:** Vista atractiva e individual con la foto cargada previamente.
* **Sección de Bitácoras:** Visualización de las bitácoras registradas por el profesional.
* **Sección de Informe General:** Consolidado dinámico generado automáticamente a partir de las bitácoras diarias registradas para el paciente.
* **Botones de Acción en Parte Inferior:**
  1. **Botón "Modificar":** Habilita los campos de información para su edición. Al hacer clic nuevamente, guarda los cambios realizados.
  2. **Botón "Consultar Documentos":** Permite visualizar y consultar los archivos cargados durante la creación del paciente.
  3. **Botón "Añadir Bitácora" (Solo para Rol Profesional):** Despliega formulario con:
     * Campo para describir la **actividad diaria**.
     * Campo para registrar el **comportamiento del paciente**.
     * Campo para describir el **manejo brindado** en caso de mal comportamiento o crisis.
     * Botón para adjuntar/añadir el informe.

---

### 1.4. Ajustes Exclusivos para el Rol Profesional
* En la opción "Pacientes Asignados", **eliminar el listado de informes recientes**.
* Conservar únicamente la lista de pacientes asignados con los campos y comportamientos descritos en la sección 1.2.

---

## 2. OPTIMIZACIÓN DE DISEÑO Y MAQUETACIÓN (LAYOUT & FULL-WIDTH)

* **Aprovechamiento del Espacio en Pantalla:**
  * Ajustar el contenedor principal y el `iframe` para que utilicen el **100% del alto y ancho disponible** de la pantalla (`width: 100%`, `height: 100vh` / `calc(100vh - padding)`), eliminando márgenes o espacios en blanco excesivos abajo y a los lados.
* **Ancho de Paneles y Tablas:**
  * La tarjeta del "Panel Administrador" y el contenedor de la tabla "Listado de pacientes" deben expandirse a lo largo de todo el panel derecho para no verse comprimidos ni con espacio sobrante a la derecha.
* **Proporción del Sidebar:**
  * Mantener el menú lateral compacto a un ancho fijo (ej. `220px` a `250px`), otorgando el resto del espacio en pantalla al contenedor principal.