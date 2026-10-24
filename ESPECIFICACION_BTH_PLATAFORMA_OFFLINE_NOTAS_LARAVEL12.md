# ESPECIFICACIÓN DE IMPLEMENTACIÓN — PLATAFORMA OFFLINE DE NOTAS ESCOLARES

> **Proyecto BTH (Bachillerato Técnico Humanístico) — Sistemas Informáticos**  
> **Unidad Educativa Técnico Humanístico Maestro Pitágoras II — La Guardia, Santa Cruz, Bolivia, 2026**  
> **Base documental:** *Copia de Proyecto de Quispe y Copa 3 (1).docx*, «Plataforma Offline para la Administración de Notas Escolares».  
> **Objetivo de este archivo:** indicar con precisión qué debe programarse en Laravel 12: migraciones, modelos, controladores, rutas, vistas, validaciones, permisos, reportes, respaldos, pruebas e instalación.  
> **Naturaleza del documento:** especificación y lista de implementación. **No significa que el código ya exista**.

---

## 0. Instrucciones fundamentales para quien programe el proyecto

1. **Construir un sistema real y funcional, no solo pantallas de demostración.** Los formularios deben guardar y consultar datos reales de MySQL/MariaDB; los reportes deben leer esas mismas calificaciones.
2. **Mantener el nivel BTH de colegio.** Código claro, arquitectura MVC sencilla, nombres coherentes y explicación comprensible para las estudiantes que defenderán el proyecto. No convertirlo en un software empresarial o universitario.
3. **Respetar el documento de origen.** Las tablas principales serán las ocho del Anexo N.º 6. Los módulos serán los nueve descritos en 4.1.1; las pantallas partirán de la tabla de 4.1, y las pruebas incluirán LC-01 a LC-08 y PF-01 a PF-05.
4. **Primera versión 100 % local:** sin servicios en la nube, sin conexión obligatoria a Internet, sin APIs externas, sin CDN, sin fuentes ni iconos descargados al abrir las páginas.
5. **No inventar reglas oficiales de evaluación.** Antes de fijar el rango de notas, ponderaciones, redondeo, aprobación o número obligatorio de periodos, comprobar el reglamento educativo aplicable a 2026 y acordar las reglas con el docente guía. Registrar la decisión y configurar el sistema en consecuencia.
6. **No usar datos reales de menores de edad durante el desarrollo ni la defensa.** Crear estudiantes, docentes y notas ficticios.
7. **No dar acceso automático a padres o estudiantes.** En el documento se describen expresamente los roles `administrador` y `docente`; otras consultas quedan sujetas a autorización institucional.
8. **No marcar funcionalidades como terminadas si no se probaron.** Toda casilla de esta especificación empieza pendiente hasta verificarse con evidencia.

### 0.1. Qué exige el documento y qué es una decisión técnica

| Origen | Alcance |
|---|---|
| **Expresamente solicitado** | Laravel 12, PHP, Blade, Bootstrap 5.3, JavaScript, MySQL/MariaDB, XAMPP, funcionamiento offline, usuarios y roles, estudiantes, cursos/matrículas, materias/asignaciones, periodos, notas, promedios, reportes y respaldos. |
| **Expresamente documentado en Anexo 6** | Tablas: `usuarios`, `estudiantes`, `cursos`, `materias`, `matriculas`, `asignaciones`, `periodos`, `notas`; sus columnas principales se respetan en esta propuesta. |
| **Decisiones de implementación recomendadas** | Nombres concretos de clases, endpoints, índices SQL, Request Validators, servicios de cálculo/respaldo, organización de las vistas, CSV como exportación sencilla e integración con el sistema de sesiones de Laravel. |
| **Necesita confirmación antes de uso real** | Fórmula oficial de promedio, escala de calificación, cantidad/nombres de periodos, reglas de matrícula por gestión y si una materia de un curso puede tener más de un docente responsable. |
| **Fuera de la primera versión** | App móvil, portal de familias, boletas oficiales complejas, estadísticas avanzadas, SIE o integración ministerial, servicios cloud, notificaciones por WhatsApp y múltiples colegios. |

---

## 1. Alcance funcional: los nueve módulos obligatorios

### M01. Acceso al sistema y roles

- Inicio de sesión con **usuario** y contraseña, no con correo electrónico obligatorio.
- Usuarios con estado activo/inactivo.
- Contraseñas cifradas mediante el hash de Laravel.
- Acceso a funciones según rol: `administrador` o `docente`.
- Cierre de sesión; mensajes comprensibles si las credenciales no son válidas.
- El administrador puede crear, editar y desactivar cuentas docentes.
- **No** existe registro público de usuarios.

### M02. Estudiantes

- Registrar estudiante con código escolar, nombres, apellidos y estado.
- Listar, buscar por código/apellido, consultar, editar y activar/desactivar.
- Mostrar el curso actual a partir de la matrícula, sin duplicar el curso en `estudiantes`.
- Impedir códigos repetidos y datos obligatorios vacíos.

### M03. Cursos y matrículas

- Crear cursos con nombre, paralelo y gestión (ejemplo de datos ficticios: `4.º de Secundaria`, `A`, `2026`).
- Registrar qué estudiante pertenece a qué curso durante una gestión.
- Consultar el listado de estudiantes matriculados por curso.
- Evitar matrículas duplicadas y asociaciones incompatibles.
- Mostrar en estudiantes su curso correspondiente a la gestión seleccionada.

### M04. Materias y asignaciones docentes

- Crear materias con nombre y sigla.
- Asignar a un docente una materia, curso y gestión.
- Mostrar al docente **solo las asignaciones que le corresponden**.
- Evitar asignaciones duplicadas e inconsistentes.
- El docente no administra por su cuenta materias, cursos ni asignaciones.

### M05. Periodos académicos

- Crear y editar periodos con nombre, número y gestión.
- Ordenar periodos por su número.
- Filtrar calificaciones y reportes por gestión y periodo.
- **No programar tres trimestres como regla obligatoria** hasta verificar las disposiciones aplicables. El documento dice «trimestres o periodos».

### M06. Registro y modificación de notas

- Seleccionar curso, materia y periodo; mostrar la nómina matriculada.
- Permitir ingresar **calificación** y **observación** por estudiante.
- Guardar nuevas calificaciones y modificar las existentes.
- Validar el rango y los valores aceptados según la configuración académica aprobada.
- No permitir doble nota para la misma matrícula, asignación y periodo.
- Verificar en el servidor que el docente está asignado a esa materia/curso/gestión.
- Confirmar los registros guardados y mostrar errores sin perder los datos del formulario.

### M07. Promedios

- Calcular y consultar los promedios desde las notas guardadas.
- Permitir cálculo por estudiante/materia y consulta por curso/periodo cuando corresponda.
- Usar **una regla de cálculo centralizada** para pantalla y reporte.
- Tratar las notas faltantes como faltantes, no como cero por defecto.
- Mostrar cuándo el resultado es provisional por periodos sin registrar.
- **No presentar un promedio como oficial hasta validar las reglas con normativa y docente guía.**

### M08. Reportes

- Buscar por curso y periodo, con filtros complementarios por gestión/materia si son útiles.
- Mostrar consolidado de estudiantes, materias, calificaciones y promedios disponibles.
- Vista previa, botón **Imprimir** y botón **Exportar**.
- Primera opción suficiente para BTH: página imprimible y exportación **CSV** compatible con hojas de cálculo, sin depender de Internet.
- Los reportes deben coincidir exactamente con la información de la base de datos.

### M09. Copias de seguridad

- Acceso exclusivo del administrador.
- Crear copia local de la base de datos y almacenarla fuera de `public/`.
- Mostrar respaldos disponibles: nombre, fecha y tamaño.
- Permitir descargar/copiar una copia a una memoria USB.
- Restaurar un respaldo **con confirmación visible** y precauciones de seguridad.
- Comprobar con datos ficticios que un respaldo realmente puede restaurarse.

---

## 2. Roles y autorización

Solo se implementarán dos roles en la versión BTH.

| Función | Administrador | Docente |
|---|:---:|:---:|
| Iniciar y cerrar sesión | Sí | Sí |
| Panel principal | Sí | Sí |
| Crear/editar/desactivar usuarios | Sí | No |
| Mantener estudiantes | Sí | No |
| Mantener cursos, materias y matrículas | Sí | No |
| Crear/editar asignaciones docentes | Sí | No |
| Configurar periodos | Sí | No |
| Ver sus cursos y estudiantes asignados | Sí, consulta general | Sí, solo los asignados |
| Registrar y modificar calificaciones | No por defecto* | Sí, solo las propias |
| Consultar promedios/reportes | Sí | Solo de asignaciones propias |
| Crear, descargar y restaurar respaldos | Sí | No |

\* **Decisión prudente:** el documento asigna la gestión de calificaciones al docente y la configuración/respaldo al administrador. Si el colegio autoriza explícitamente correcciones administrativas, añadir un permiso controlado y documentarlo; **no conceder edición universal de notas por omisión**.

**Control imprescindible:** ocultar botones no reemplaza la seguridad. Cada acción debe comprobar el rol y, para docentes, la pertenencia de la asignación. Introducir manualmente la URL de otro curso no debe revelar ni modificar notas.

---

## 3. Recorridos que deben funcionar

### 3.1. Administrador

`Iniciar sesión → Panel → Crear docente → Registrar materia y curso → Matricular estudiantes → Asignar docente al curso/materia → Crear periodos → Consultar reportes → Crear respaldo → Cerrar sesión`.

### 3.2. Docente

`Iniciar sesión → Panel docente → Elegir uno de mis cursos/materias → Elegir periodo → Ver estudiantes matriculados → Ingresar/editar notas y observaciones → Guardar → Consultar promedios → Imprimir o exportar reporte → Cerrar sesión`.

### 3.3. Recuperación

`Administrador → Respaldos → Crear copia preventiva actual → Seleccionar respaldo válido → Confirmar restauración → Restaurar → Verificar estudiantes/notas ficticios → Registrar resultado de la prueba`.

---

## 4. Base de datos: conservar las ocho tablas del Anexo 6

> **Convención elegida:** nombres de tablas y campos en español, igual que el documento. Cada tabla tendrá `id` y, por comodidad de Laravel, `created_at` / `updated_at` cuando corresponda. Los campos complementarios de seguridad o índices son decisiones técnicas, no entidades nuevas inventadas.

### 4.1. `usuarios`

**Migración:** `create_usuarios_table.php`  
**Modelo:** `app/Models/Usuario.php` (extiende `Illuminate\Foundation\Auth\User` / `Authenticatable`).

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `nombre` | `string(150)` | Obligatorio |
| `usuario` | `string(50)` | Obligatorio, `UNIQUE` |
| `password` | `string` | Hash, nunca texto plano |
| `rol` | `string(20)` | `administrador` o `docente` |
| `activo` | `boolean` | Predeterminado `true` |
| `remember_token` | `string`, nullable | Opcional para sesiones de Laravel |
| `created_at`, `updated_at` | timestamps | Laravel |

Reglas: no permitir usuarios duplicados; no permitir que una cuenta docente acceda a rutas administrativas; no eliminar físicamente un docente con asignaciones vinculadas; desactivarlo. Configurar `config/auth.php` para que el proveedor use el modelo `Usuario`, no el `User`/`users` de ejemplo.

### 4.2. `estudiantes`

**Migración:** `create_estudiantes_table.php`  
**Modelo:** `Estudiante.php`.

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `codigo` | `string(30)` | Obligatorio, `UNIQUE` |
| `nombres` | `string(120)` | Obligatorio |
| `apellidos` | `string(120)` | Obligatorio |
| `estado` | `string(20)` | `activo` o `inactivo` |
| timestamps | timestamps | Laravel |

No introducir fecha de nacimiento, fotografía, teléfono de padres o documentación sensible porque **no están pedidos**. El curso se obtiene de `matriculas`.

### 4.3. `cursos`

**Migración:** `create_cursos_table.php`  
**Modelo:** `Curso.php`.

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `nombre` | `string(100)` | Obligatorio |
| `paralelo` | `string(20)` | Obligatorio |
| `gestion` | `unsignedSmallInteger` | Año académico, ej. 2026 |
| timestamps | timestamps | Laravel |

Índice `UNIQUE(nombre, paralelo, gestion)` para evitar crear dos veces el mismo curso y paralelo en la misma gestión.

### 4.4. `materias`

**Migración:** `create_materias_table.php`  
**Modelo:** `Materia.php`.

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `nombre` | `string(120)` | Obligatorio |
| `sigla` | `string(20)` | Obligatorio; `UNIQUE` en este alcance |
| timestamps | timestamps | Laravel |

### 4.5. `matriculas`

**Migración:** `create_matriculas_table.php`  
**Modelo:** `Matricula.php`.

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `estudiante_id` | `foreignId` | FK → `estudiantes.id` |
| `curso_id` | `foreignId` | FK → `cursos.id` |
| `gestion` | `unsignedSmallInteger` | Debe coincidir con `cursos.gestion` |
| timestamps | timestamps | Laravel |

**Propuesta BTH:** una matrícula por estudiante por gestión, índice `UNIQUE(estudiante_id, gestion)`. Esta decisión debe revisarse si el colegio necesita traslados de curso dentro del mismo año. No permitir matrícula de un estudiante inactivo. Restringir el borrado si la matrícula tiene notas.

### 4.6. `asignaciones`

**Migración:** `create_asignaciones_table.php`  
**Modelo:** `Asignacion.php`.

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `docente_id` | `foreignId` | FK → `usuarios.id` |
| `materia_id` | `foreignId` | FK → `materias.id` |
| `curso_id` | `foreignId` | FK → `cursos.id` |
| `gestion` | `unsignedSmallInteger` | Debe coincidir con `cursos.gestion` |
| timestamps | timestamps | Laravel |

Comprobar que `docente_id` pertenezca a un usuario **activo con rol docente**. Para una primera versión sencilla, aplicar `UNIQUE(curso_id, materia_id, gestion)`, es decir, un docente responsable por materia/curso/gestión. **Esta exclusividad es una decisión pendiente de validar**, no una norma impuesta por el documento. Si existe codocencia, ajustar esta política antes de desarrollar el registro de notas.

### 4.7. `periodos`

**Migración:** `create_periodos_table.php`  
**Modelo:** `Periodo.php`.

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `nombre` | `string(80)` | Ej.: «Primer periodo» |
| `numero` | `unsignedTinyInteger` | Orden positivo |
| `gestion` | `unsignedSmallInteger` | Año académico |
| timestamps | timestamps | Laravel |

Índice `UNIQUE(gestion, numero)`. La cantidad de periodos se administra según normativa; no fijar 3 de manera irreversible.

### 4.8. `notas`

**Migración:** `create_notas_table.php`  
**Modelo:** `Nota.php`.

| Campo | Tipo sugerido | Condición |
|---|---|---|
| `id` | `bigint` | PK |
| `matricula_id` | `foreignId` | FK → `matriculas.id` |
| `asignacion_id` | `foreignId` | FK → `asignaciones.id` |
| `periodo_id` | `foreignId` | FK → `periodos.id` |
| `nota` | `decimal(5,2)`, nullable* | Dentro del rango académico configurado |
| `observacion` | `text`, nullable | Comentario opcional |
| timestamps | timestamps | Laravel |

\* Para una **fila de nota efectivamente registrada**, la calificación debe existir y ser válida. No es necesario crear una fila vacía por cada alumno: la planilla puede mostrar estudiantes sin registro y guardar solo los que tengan nota. `nullable` es una opción técnica si se admiten borradores; para el alcance más simple, preferir `nota` **NOT NULL** y no guardar filas incompletas.

Índice obligatorio: `UNIQUE(matricula_id, asignacion_id, periodo_id)` para impedir duplicados.

**Validaciones de integridad antes de guardar:**

- `matriculas.curso_id == asignaciones.curso_id`.
- `matriculas.gestion == asignaciones.gestion == periodos.gestion`.
- El docente autenticado es `asignaciones.docente_id`.
- La asignación pertenece realmente a esa materia y curso.
- La nota cumple el formato/rango verificado; jamás confiar en la validación de JavaScript únicamente.
- La calificación está asociada a un estudiante matriculado, nunca a un identificador enviado sin comprobar.

### 4.9. Relaciones del modelo

```text
usuarios (docentes) 1 ─── N asignaciones
estudiantes        1 ─── N matriculas
cursos             1 ─── N matriculas
cursos             1 ─── N asignaciones
materias           1 ─── N asignaciones
matriculas         1 ─── N notas
asignaciones       1 ─── N notas
periodos           1 ─── N notas
```

> No crear tablas `promedios`, `reportes` o `respaldos` para este alcance: los **promedios** se calculan, los **reportes** se generan con consultas y los **respaldos** son archivos protegidos. Las tablas internas de Laravel para sesiones, caché o cola pueden existir si su configuración las necesita, pero **no forman parte de las ocho tablas académicas**.

### 4.10. Orden de ejecución de migraciones

1. `usuarios`
2. `estudiantes`
3. `cursos`
4. `materias`
5. `periodos`
6. `matriculas`
7. `asignaciones`
8. `notas`

Usar claves foráneas reales y `restrictOnDelete()` en registros vinculados a notas. Evitar borrados en cascada que eliminen calificaciones por accidente. Si una entidad tiene dependencias, mostrar «No se puede eliminar porque tiene registros asociados»; ofrecer desactivación cuando exista ese estado.

---

## 5. Archivos que debe contener el proyecto Laravel

**Estructura propuesta (se muestran los archivos relevantes, además de los que Laravel genera por defecto):**

```text
plataforma-notas/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/LoginController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── UsuarioController.php
│   │   │   ├── EstudianteController.php
│   │   │   ├── CursoController.php
│   │   │   ├── MateriaController.php
│   │   │   ├── MatriculaController.php
│   │   │   ├── AsignacionController.php
│   │   │   ├── PeriodoController.php
│   │   │   ├── NotaController.php
│   │   │   ├── PromedioController.php
│   │   │   ├── ReporteController.php
│   │   │   └── RespaldoController.php
│   │   ├── Middleware/
│   │   │   └── VerificarRol.php
│   │   └── Requests/
│   │       ├── LoginRequest.php
│   │       ├── GuardarUsuarioRequest.php
│   │       ├── GuardarEstudianteRequest.php
│   │       ├── GuardarCursoRequest.php
│   │       ├── GuardarMateriaRequest.php
│   │       ├── GuardarMatriculaRequest.php
│   │       ├── GuardarAsignacionRequest.php
│   │       ├── GuardarPeriodoRequest.php
│   │       └── GuardarPlanillaNotasRequest.php
│   ├── Models/
│   │   ├── Usuario.php
│   │   ├── Estudiante.php
│   │   ├── Curso.php
│   │   ├── Materia.php
│   │   ├── Matricula.php
│   │   ├── Asignacion.php
│   │   ├── Periodo.php
│   │   └── Nota.php
│   └── Services/
│       ├── CalculoPromediosService.php
│       └── RespaldoService.php
├── bootstrap/
│   └── app.php                 # Alias del middleware de rol (Laravel 12)
├── config/
│   ├── auth.php                # Provider de Usuario
│   └── academico.php           # Reglas verificadas de calificación
├── database/
│   ├── migrations/
│   │   ├── ..._create_usuarios_table.php
│   │   ├── ..._create_estudiantes_table.php
│   │   ├── ..._create_cursos_table.php
│   │   ├── ..._create_materias_table.php
│   │   ├── ..._create_periodos_table.php
│   │   ├── ..._create_matriculas_table.php
│   │   ├── ..._create_asignaciones_table.php
│   │   └── ..._create_notas_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UsuarioSeeder.php
│       └── DatosDemostracionSeeder.php
├── public/
│   ├── build/                   # Assets compilados y locales de Vite
│   └── images/escudo-colegio.*  # Solo con autorización de uso
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
│       ├── layouts/app.blade.php
│       ├── components/alertas.blade.php
│       ├── auth/login.blade.php
│       ├── dashboard/index.blade.php
│       ├── usuarios/{index,create,edit,show,_form}.blade.php
│       ├── estudiantes/{index,create,edit,show,_form}.blade.php
│       ├── cursos/{index,create,edit,show,_form}.blade.php
│       ├── materias/{index,create,edit,show,_form}.blade.php
│       ├── matriculas/{index,create,edit,_form}.blade.php
│       ├── asignaciones/{index,create,edit,_form}.blade.php
│       ├── periodos/{index,create,edit,_form}.blade.php
│       ├── notas/{index,planilla}.blade.php
│       ├── promedios/index.blade.php
│       ├── reportes/{index,consolidado,imprimir}.blade.php
│       ├── respaldos/index.blade.php
│       └── errors/{403,404}.blade.php
├── routes/web.php
├── storage/app/private/respaldos/ # Respaldo fuera de public/
├── tests/Feature/
│   ├── AutenticacionTest.php
│   ├── RolesTest.php
│   ├── GestionAcademicaTest.php
│   ├── NotasTest.php
│   ├── PromediosTest.php
│   └── ReportesTest.php
├── docs/
│   ├── MANUAL_INSTALACION.md
│   ├── MANUAL_USUARIO.md
│   ├── PRUEBAS_BTH.md
│   └── REGLAS_ACADEMICAS.md
├── .env.example
├── composer.json
├── package.json
└── README.md
```

**Importante:** las llaves `{index,create,...}` en este árbol son una **abreviatura visual**; en el repositorio deben existir archivos individuales `index.blade.php`, `create.blade.php`, etc. No crear archivos con llaves en el nombre.

---

## 6. Modelos: atributos y relaciones Eloquent

### `Usuario`

- Extender `Authenticatable` e implementar `protected $table = 'usuarios';`.
- Campos asignables: `nombre`, `usuario`, `password`, `rol`, `activo`.
- Ocultar `password`, `remember_token` al serializar.
- Aplicar cast de contraseña mediante `hashed` o `Hash::make`, sin doble hashing.
- Relaciones: `hasMany(Asignacion::class, 'docente_id')`.
- Métodos sencillos: `esAdministrador()` y `esDocente()`.

### `Estudiante`

- Tabla `estudiantes`; `hasMany(Matricula::class)`.
- Accessor opcional `nombre_completo`.
- El curso se consulta mediante la matrícula de la gestión activa/seleccionada.

### `Curso`

- Tabla `cursos`; `hasMany(Matricula::class)` y `hasMany(Asignacion::class)`.
- Accesor de presentación `nombre + paralelo + gestion`.

### `Materia`

- Tabla `materias`; `hasMany(Asignacion::class)`.

### `Matricula`

- Tabla `matriculas`; `belongsTo(Estudiante::class)`; `belongsTo(Curso::class)`; `hasMany(Nota::class)`.

### `Asignacion`

- Tabla `asignaciones`; `belongsTo(Usuario::class, 'docente_id')` (relación denominada `docente`); `belongsTo(Curso::class)`; `belongsTo(Materia::class)`; `hasMany(Nota::class)`.

### `Periodo`

- Tabla `periodos`; `hasMany(Nota::class)`.

### `Nota`

- Tabla `notas`; `belongsTo(Matricula::class)`; `belongsTo(Asignacion::class)`; `belongsTo(Periodo::class)`.
- Cast `nota` a `decimal:2` para presentación consistente.

**Consultas:** usar `with(...)` para cargar relaciones en listas y reportes. No programar SQL repetido directamente en Blade.

---

## 7. Controladores y responsabilidades

| Controlador | Métodos mínimos | Responsabilidad |
|---|---|---|
| `LoginController` | `showLogin`, `login`, `logout` | Autenticar por `usuario`, revisar `activo`, cerrar sesión de forma segura. |
| `DashboardController` | `index` | Mostrar menú, resumen y accesos según rol. |
| `UsuarioController` | `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`* | Administrar usuarios y desactivar cuentas con dependencias. |
| `EstudianteController` | CRUD + `show` | Búsqueda por código/apellido, estado y curso derivado de matrícula. |
| `CursoController` | CRUD + `show` | Gestión de cursos/paralelos/gestiones; consultar matriculados. |
| `MateriaController` | CRUD + `show` | Catálogo de materias y siglas. |
| `MatriculaController` | `index`, `create`, `store`, `edit`, `update`, `destroy`* | Matricular estudiantes sin duplicaciones. |
| `AsignacionController` | `index`, `create`, `store`, `edit`, `update`, `destroy`* | Vincular docentes, cursos, materias y gestión. |
| `PeriodoController` | `index`, `create`, `store`, `edit`, `update`, `destroy`* | Crear y ordenar periodos de evaluación. |
| `NotaController` | `index`, `planilla`, `guardarPlanilla` | Filtrar asignaciones, construir planilla y guardar/editar notas autorizadas. |
| `PromedioController` | `index` | Mostrar promedios calculados por el servicio académico. |
| `ReporteController` | `index`, `consolidado`, `imprimir`, `exportar` | Vista previa, reporte imprimible, descarga CSV. |
| `RespaldoController` | `index`, `store`, `download`, `restore` | Listar, generar, descargar y restaurar archivos controlados. |

\* `destroy` **no significa borrado obligatorio**. Debe impedirse cuando existan relaciones históricas. Para `usuarios` y `estudiantes`, es preferible desactivar; si se conserva la ruta DELETE, puede denegar el borrado con mensaje claro. Nunca perder notas vinculadas.

### 7.1. Cómo programar `NotaController::guardarPlanilla`

1. Verificar autenticación y rol `docente`.
2. Recibir `asignacion_id`, `periodo_id` y colección de notas por `matricula_id`.
3. Obtener desde la base de datos la asignación perteneciente al docente autenticado; si no existe, responder **403**.
4. Comprobar que el periodo corresponde a la gestión de la asignación.
5. Obtener matrículas válidas del curso de la asignación; rechazar estudiantes ajenos.
6. Validar cada nota según la regla académica configurada. Observación opcional y con longitud limitada.
7. Guardar dentro de una transacción; insertar o actualizar por (`matricula_id`, `asignacion_id`, `periodo_id`).
8. Si falta una nota y no existía registro, dejarla **sin registrar**; no insertar cero automáticamente.
9. No permitir borrar una nota existente solo porque el campo llegó vacío; definir una acción explícita y confirmada para anularla, si el colegio la aprueba.
10. Devolver confirmación y recargar la misma planilla con los valores guardados.

### 7.2. `CalculoPromediosService`

- Único responsable del cálculo; **no repetir fórmulas en Blade, JavaScript y controlador**.
- Recibir estudiante/matrícula, asignación y periodos de la misma gestión.
- Comprobar que no hay mezclas de cursos, materias o años.
- Calcular con escala, precisión, ponderaciones y redondeo **documentados y verificados**.
- Para la demostración técnica puede usarse una regla aritmética **solo si está autorizada como ejemplo**, sin presentarla como cálculo oficial del Ministerio.
- Devolver resultado más su estado: `completo`, `incompleto` o `sin_notas`, según los periodos requeridos.
- Usar el mismo servicio para pantalla de promedios y reportes.

### 7.3. `RespaldoService`

- Encapsular la ejecución segura de las herramientas locales de MySQL/MariaDB.
- Generar nombre de archivo controlado y ruta privada.
- Comprobar que los comandos existen, que el archivo se generó y que tiene contenido.
- Descargar archivos únicamente tras validar su pertenencia al directorio de respaldos.
- Antes de restaurar, generar una copia preventiva y solicitar confirmación reforzada.
- Restaurar en condiciones controladas; comprobar éxito o registrar error y detener el proceso.
- No concatenar comandos de consola con entradas del usuario; usar procesos con argumentos separados y gestión segura de credenciales.

---

## 8. Rutas del sistema — `routes/web.php`

Las siguientes rutas constituyen la propuesta mínima, con nombres coherentes para `route(...)` en Blade.

| Método | URL | Nombre | Acceso |
|---|---|---|---|
| GET | `/login` | `login` | Invitado |
| POST | `/login` | `login.attempt` | Invitado |
| POST | `/logout` | `logout` | Autenticado |
| GET | `/` | `dashboard` | Autenticado |
| GET/POST/etc. | `/usuarios` y `/usuarios/{usuario}` | `usuarios.*` | Administrador |
| GET/POST/etc. | `/estudiantes` y `/estudiantes/{estudiante}` | `estudiantes.*` | Administrador |
| GET/POST/etc. | `/cursos` y `/cursos/{curso}` | `cursos.*` | Administrador |
| GET/POST/etc. | `/materias` y `/materias/{materia}` | `materias.*` | Administrador |
| GET/POST/etc. | `/matriculas` y `/matriculas/{matricula}` | `matriculas.*` | Administrador |
| GET/POST/etc. | `/asignaciones` y `/asignaciones/{asignacion}` | `asignaciones.*` | Administrador |
| GET/POST/etc. | `/periodos` y `/periodos/{periodo}` | `periodos.*` | Administrador |
| GET | `/notas` | `notas.index` | Docente |
| GET | `/notas/planilla` | `notas.planilla` | Docente |
| POST | `/notas/planilla` | `notas.guardar` | Docente |
| GET | `/promedios` | `promedios.index` | Administrador / Docente autorizado |
| GET | `/reportes` | `reportes.index` | Administrador / Docente autorizado |
| GET | `/reportes/consolidado` | `reportes.consolidado` | Administrador / Docente autorizado |
| GET | `/reportes/imprimir` | `reportes.imprimir` | Administrador / Docente autorizado |
| GET | `/reportes/exportar` | `reportes.exportar` | Administrador / Docente autorizado |
| GET | `/respaldos` | `respaldos.index` | Administrador |
| POST | `/respaldos` | `respaldos.store` | Administrador |
| GET | `/respaldos/{archivo}/descargar` | `respaldos.download` | Administrador |
| POST | `/respaldos/{archivo}/restaurar` | `respaldos.restore` | Administrador |

### 8.1. Organización sugerida en código

```php
// Fragmento orientativo de routes/web.php; importar las clases necesarias.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('rol:administrador')->group(function () {
        Route::resource('usuarios', UsuarioController::class);
        Route::resource('estudiantes', EstudianteController::class);
        Route::resource('cursos', CursoController::class);
        Route::resource('materias', MateriaController::class);
        Route::resource('matriculas', MatriculaController::class)->except(['show']);
        Route::resource('asignaciones', AsignacionController::class)->except(['show']);
        Route::resource('periodos', PeriodoController::class)->except(['show']);

        Route::get('/respaldos', [RespaldoController::class, 'index'])->name('respaldos.index');
        Route::post('/respaldos', [RespaldoController::class, 'store'])->name('respaldos.store');
        Route::get('/respaldos/{archivo}/descargar', [RespaldoController::class, 'download'])->name('respaldos.download');
        Route::post('/respaldos/{archivo}/restaurar', [RespaldoController::class, 'restore'])->name('respaldos.restore');
    });

    Route::middleware('rol:docente')->group(function () {
        Route::get('/notas', [NotaController::class, 'index'])->name('notas.index');
        Route::get('/notas/planilla', [NotaController::class, 'planilla'])->name('notas.planilla');
        Route::post('/notas/planilla', [NotaController::class, 'guardarPlanilla'])->name('notas.guardar');
    });

    Route::middleware('rol:administrador,docente')->group(function () {
        Route::get('/promedios', [PromedioController::class, 'index'])->name('promedios.index');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/consolidado', [ReporteController::class, 'consolidado'])->name('reportes.consolidado');
        Route::get('/reportes/imprimir', [ReporteController::class, 'imprimir'])->name('reportes.imprimir');
        Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');
    });
});
```

**Laravel 12:** el alias de middleware `rol` se registra en **`bootstrap/app.php`**, mediante configuración `withMiddleware(...)`. La clase `VerificarRol` debe admitir varios roles cuando se envían separados por comas. Para reportes/promedios compartidos, **el controlador también filtra por asignaciones del docente**; pasar el middleware de rol no autoriza todas las filas.

**Verificar** que `php artisan route:list` presente todas las rutas esperadas. Los botones HTML que modifican datos deben usar POST/PUT/PATCH/DELETE con `@csrf` y `@method` cuando corresponda.

---

## 9. Vistas Blade: diseño y comportamiento de cada pantalla

### Diseño global — `layouts/app.blade.php`

- Encabezado con el nombre «Unidad Educativa Técnico Humanístico Maestro Pitágoras II» e identidad visual permitida.
- Indicador de usuario activo y botón Cerrar sesión.
- Menú lateral: Estudiantes, Cursos, Materias, Matrículas, Asignaciones, Periodos, Notas, Promedios, Reportes, Respaldos; **mostrar solo lo autorizado**.
- Área principal amplia con títulos y acciones claras.
- Bootstrap 5.3 instalado **localmente**; CSS y JS compilados o copiados al servidor local.
- Mensajes de éxito, advertencia y validación; etiquetas claras y navegación con teclado.
- Adaptación básica a pantallas pequeñas, aunque la prioridad sea computadora de colegio.
- Estilo sobrio, educativo, legible. No hace falta diseño complejo ni animaciones decorativas.

### Pantalla A. Inicio de sesión — `auth/login.blade.php`

- Logotipo/nombre del colegio.
- `usuario` y `password`.
- Botón **Ingresar**.
- Alerta de credenciales incorrectas o cuenta desactivada (sin revelar datos innecesarios).
- No mostrar botón de registro público.

### Pantalla B. Panel principal — `dashboard/index.blade.php`

- Barra superior con usuario y rol.
- Administrador: accesos a Usuarios, Estudiantes, Cursos, Materias, Matrículas, Asignaciones, Periodos, Reportes, Respaldos.
- Docente: accesos a Mis asignaciones, Notas, Promedios, Reportes.
- Opcional sencillo: cantidades de estudiantes, cursos y materias para administrador; número de cursos/materias asignados para docente.
- Evitar gráficos y métricas avanzadas innecesarias.

### Pantalla C. Estudiantes — `estudiantes/index.blade.php`

- Buscar por **código o apellido**.
- Tabla: `Código | Nombre completo | Curso de la gestión | Estado | Acciones`.
- Botones **Buscar**, **Agregar estudiante**, **Consultar**, **Editar**.
- Crear/editar: campos código, nombres, apellidos y estado.
- Mostrar mensaje cuando el estudiante aún no tiene matrícula en la gestión.

### Pantalla D. Notas — `notas/index.blade.php` y `notas/planilla.blade.php`

- Selección de **Curso**, **Materia** y **Periodo**; puede elegirse primero la gestión.
- Para docentes, la lista curso/materia procede exclusivamente de `asignaciones` propias.
- Planilla: `Estudiante | Calificación | Observación`.
- Precargar notas ya existentes y dejar claramente vacías las no registradas.
- Botones **Guardar** y **Calcular/Consultar promedio**.
- Mensajes por fila de nota inválida, curso no autorizado o cambios guardados.

### Pantalla E. Reportes — `reportes/index.blade.php`, `consolidado.blade.php`, `imprimir.blade.php`

- Filtros principales: **Curso**, **Periodo**; añadir Gestión y Materia cuando ayuden.
- Botones **Buscar**, **Imprimir**, **Exportar CSV**.
- Vista previa con identificación de curso, gestión, periodo y tabla de notas/promedios.
- Estilo de impresión local: ocultar menú, botones y enlaces no necesarios.
- Mostrar fecha de emisión y una nota indicando si el promedio es provisional cuando corresponda.

### Pantalla F. Administración y respaldo — `usuarios/*`, `periodos/*`, `respaldos/index.blade.php`

- Formularios simples de usuarios/roles y periodos académicos.
- Respaldos: lista y botones **Crear respaldo**, **Descargar**, **Restaurar**.
- Restaurar siempre abre una confirmación clara sobre sobrescritura de datos.

### Pantallas necesarias que la tabla gráfica no dibuja separadamente

| Vista | Qué debe permitir |
|---|---|
| `cursos/*` | Registrar curso, paralelo, gestión y consultar estudiantes inscritos. |
| `materias/*` | Agregar, consultar y modificar nombre/sigla. |
| `matriculas/*` | Seleccionar estudiante, curso y gestión y registrar la matrícula. |
| `asignaciones/*` | Seleccionar docente, curso, materia y gestión. |
| `promedios/index.blade.php` | Filtrar, mostrar promedio y estado completo/incompleto. |
| `errors/403.blade.php` | Aviso amigable de «No tiene permiso». |
| `errors/404.blade.php` | Aviso amigable de «Registro no encontrado». |

Para CRUD, reutilizar `_form.blade.php` dentro de crear/editar y mantener `old(...)`, mensajes de error y etiquetas accesibles. Los listados pueden usar paginación básica de Laravel.

---

## 10. Form Requests y validaciones obligatorias

| Request | Verificaciones mínimas |
|---|---|
| `LoginRequest` | Usuario/contraseña obligatorios; limitar intentos fallidos desde controlador o RateLimiter. |
| `GuardarUsuarioRequest` | Nombre, usuario único, rol permitido y estado válido; password seguro en creación, opcional en edición. |
| `GuardarEstudianteRequest` | Código único, nombres/apellidos presentes y estado permitido. |
| `GuardarCursoRequest` | Nombre, paralelo, gestión y ausencia de curso duplicado. |
| `GuardarMateriaRequest` | Nombre/sigla y sigla única. |
| `GuardarMatriculaRequest` | Estudiante/curso válidos, gestión coincidente, matrícula no duplicada; estudiante activo. |
| `GuardarAsignacionRequest` | Usuario activo con rol docente, curso/materia existentes, gestión coherente, asignación no duplicada. |
| `GuardarPeriodoRequest` | Nombre, número positivo, gestión y número de periodo único por gestión. |
| `GuardarPlanillaNotasRequest` | Asignación, periodo, arreglo de matrículas, nota dentro de escala autorizada, observación opcional. |

**Reglas adicionales:**

- Validar tanto al crear como al actualizar; en `unique` excluir el propio registro cuando corresponda.
- Comprobar autorizaciones en el controlador/policy además del Request.
- Validar la gestión en el servidor; no confiar en `<select>` ocultos ni en filtros alterados desde DevTools.
- No aceptar texto no numérico como nota ni valores que excedan la precisión permitida.
- Laravel Blade escapa las observaciones y nombres con `{{ ... }}`; no imprimir HTML introducido por usuarios.
- Usar transacciones en operaciones que guardan varias notas.
- Evitar exposición masiva: rellenar atributos permitidos, no llamar `update($request->all())` sin filtrado.

### 10.1. Configuración académica — `config/academico.php`

Centralizar al menos:

```text
escala_minima             → pendiente de validación oficial
escala_maxima             → pendiente de validación oficial
decimales_permitidos      → pendiente de validación
metodo_promedio           → pendiente de validación
ponderacion_por_periodo   → pendiente de validación, si aplica
regla_redondeo            → pendiente de validación
cantidad_periodos         → pendiente de validación
```

El archivo de configuración **no debe contener valores supuestamente oficiales sin fuente**. Para ensayar la interfaz con datos ficticios se puede definir una configuración DEMO, visible como tal, y registrar en `docs/REGLAS_ACADEMICAS.md` qué se validó antes de usar notas reales. Si está incompleta la configuración obligatoria, impedir guardar notas reales hasta completarla.

---

## 11. Autenticación y seguridad básica

1. Login personalizado en Blade/Bootstrap, compatible con Laravel 12; sin instalar interfaces React/Vue/Livewire para esta versión.
2. `Auth::attempt(...)` usando `usuario`, `password` y `activo=true`, con manejo de error; regenerar sesión después de iniciar correctamente.
3. Al cerrar sesión: cerrar guard, invalidar sesión y regenerar token CSRF.
4. Incluir middleware `auth` y `rol`; denegar acceso con 403 a personas no autorizadas.
5. No exponer credenciales en rutas, HTML, JavaScript, README ni repositorio.
6. Usar contraseñas hasheadas, `@csrf` y validación de datos.
7. `APP_DEBUG=false` en la computadora donde se presentará/operará el sistema.
8. Guardar la base de datos y los archivos de respaldo en ubicación local protegida.
9. Si se usa red local, restringir el acceso a dispositivos autorizados; **offline no significa automáticamente seguro**.
10. Por sencillez, un solo rol por usuario; no hace falta un paquete empresarial de permisos.

---

## 12. Informes, impresión y exportación

### 12.1. Reporte mínimo para defender el proyecto

**Encabezado:** unidad educativa, gestión, curso, paralelo, materia si se seleccionó, periodo y fecha.  
**Detalle:** código del estudiante, nombre, calificación(es) y promedio aplicable.  
**Estado:** mostrar «Sin nota» si no existe registro; no inventar valores.  
**Acciones:** imprimir y CSV local.

### 12.2. CSV

- Generar desde Laravel usando una respuesta de descarga; sin CDN ni librería externa obligatoria.
- Incluir encabezados y respetar los filtros/roles del usuario actual.
- Escapar correctamente comillas, saltos de línea y delimitadores.
- Prevenir *formula injection* en CSV: proteger campos de texto que empiecen con `=`, `+`, `-` o `@` para que Excel no los ejecute como fórmulas.
- Comprobar tildes y caracteres en UTF-8 en equipos Windows; ajustar delimitador si se requiere para Excel local.

### 12.3. Impresión

- Usar vista Blade `reportes/imprimir.blade.php` con `@media print`.
- No depender de una API remota para generar PDF.
- Si se desea PDF sin una biblioteca adicional, permitir «Imprimir → Guardar como PDF» desde el navegador local.

---

## 13. Respaldos y restauración: no basta con un botón decorativo

### Crear respaldo

1. Solo administrador autenticado.
2. Verificar que MySQL/MariaDB está disponible y que la carpeta privada puede escribirse.
3. Exportar la base usando `mysqldump`/`mariadb-dump` compatible con la instalación de XAMPP; gestionar argumentos y secretos con cuidado.
4. Nombre controlado, por ejemplo `respaldo_notas_2026-10-17_1930.sql`.
5. Guardar en `storage/app/private/respaldos/`; nunca dentro de `public/`.
6. Comprobar integridad básica del archivo y mostrar fecha/tamaño.
7. Ofrecer descarga para guardar también en USB.

### Restaurar respaldo

1. Seleccionar exclusivamente respaldos existentes en la carpeta controlada.
2. Presentar aviso: «Restaurar reemplazará datos actuales».
3. Pedir confirmación reforzada al administrador; realizar una copia preventiva.
4. Evitar restauraciones simultáneas y nuevas escrituras mientras se ejecuta.
5. Restaurar mediante cliente de base de datos de confianza, con argumentos seguros, sin interpolación de texto libre.
6. Comprobar el resultado y solicitar nueva autenticación si se restauraron las cuentas del sistema.
7. Probar primero con información ficticia en un entorno de demostración. No hacer una prueba destructiva sobre datos reales.

**No aceptar por defecto un archivo SQL arbitrario subido por cualquier persona.** La restauración puede ejecutar instrucciones SQL, de modo que debe quedar limitada a archivos de respaldo conocidos y administradores autorizados. Documentar cómo actuar si se corta la luz o falla el proceso.

---

## 14. Modo sin Internet: requisitos verificables

- Laravel y MySQL/MariaDB ejecutándose en una computadora Windows con XAMPP.
- Acceso en el mismo equipo mediante `localhost` o un dominio local configurado.
- Opcional: varios equipos conectados a la **misma red local** sin Internet, si se configura el servidor y se autoriza el acceso.
- Bootstrap, CSS, JavaScript, iconos y fuentes servidos desde la propia aplicación.
- Sin Google Fonts, unpkg, jsDelivr, CDNs o autenticación externa.
- No depender del servidor de desarrollo de Vite para la demostración: **compilar los assets** y comprobar que estén en `public/build/`.
- La instalación inicial de dependencias puede necesitar Internet; **el funcionamiento diario del sistema no**.
- Deben continuar funcionando, al desconectar Internet: login, consulta de estudiantes/cursos, planilla, guardado de notas, promedios, impresión/exportación y respaldos.

### Configuración mínima documentada

```text
Sistema operativo: Windows (equipo institucional o de demostración)
Servidor: Apache de XAMPP o servidor local PHP compatible
Backend: Laravel 12 + PHP compatible con dicha versión
Base de datos: MySQL/MariaDB local
Cliente: navegador instalado en el equipo
Dependencias de desarrollo: Composer, Node/npm para compilar assets
Almacenamiento adicional: memoria USB para copias externas
```

En instalación Apache, dirigir el DocumentRoot a la carpeta **`public/`** de Laravel, no a la raíz del proyecto. Conservar `.env`, `storage/`, backups y código fuera de acceso público. Preparar la conexión MySQL local en `.env`, crear base de datos y ejecutar migraciones. El equipo debe poder reiniciar Apache/MySQL y volver a utilizar el sistema sin Internet.

---

## 15. Seeders y datos ficticios

### `UsuarioSeeder`

- Crear un administrador inicial y al menos un docente ficticio para pruebas.
- La clave inicial debe definirse de modo seguro fuera del código compartido o cambiarse obligatoriamente antes del uso real.
- No dejar contraseñas conocidas/hardcodeadas en el repositorio final.

### `DatosDemostracionSeeder`

- Una gestión de prueba: 2026.
- Dos o tres cursos con paralelos ficticios.
- Tres o cuatro materias.
- Dos docentes ficticios y sus respectivas asignaciones.
- Entre seis y diez estudiantes ficticios distribuidos en cursos.
- Matrículas válidas y periodos de DEMOSTRACIÓN configurados.
- Notas de ejemplo dentro de la escala DEMO; al menos una faltante para demostrar el manejo de datos incompletos.
- El seeder debe poder reutilizarse sin generar duplicados inesperados, y **no ejecutarse sobre una base institucional con datos reales**.

> La demostración debe poder realizarse desde una base limpia con comandos reproducibles. Los datos ficticios no reemplazan la evidencia de pruebas.

---

## 16. Pruebas obligatorias del documento: LC-01 a LC-08

Completar esta lista cuando el módulo exista y se haya verificado con captura o registro.

| Código | Comprobación | Evidencia que se debe obtener | Estado inicial |
|---|---|---|---|
| LC-01 | Acceso al sistema | Inicio de sesión correcto; rechazo de contraseña incorrecta | Pendiente |
| LC-02 | Roles diferenciados | Administrador ve gestión; docente no puede entrar a ella | Pendiente |
| LC-03 | Registro de estudiantes | Agregar/editar estudiante con validaciones | Pendiente |
| LC-04 | Cursos y asignaturas | Matrícula y asignación correctamente relacionadas | Pendiente |
| LC-05 | Registro de calificaciones | Guardar, consultar, editar y evitar duplicados | Pendiente |
| LC-06 | Cálculo de promedios | Resultado contrastado con regla/cálculo de referencia | Pendiente |
| LC-07 | Reportes escolares | Vista previa, impresión y exportación local | Pendiente |
| LC-08 | Respaldos y modo offline | Restaurar datos ficticios y desconectar Internet | Pendiente |

---

## 17. Pruebas funcionales PF-01 a PF-05

Estas cinco corresponden al Anexo N.º 3 y al capítulo de metodología. Para cada ejecución registrar **fecha, precondiciones, pasos, valores ficticios, resultado esperado, observado, incidencia, corrección, evidencia y estado**.

### PF-01 — Credenciales inválidas

- Paso: intentar ingresar con usuario real y contraseña incorrecta.
- Esperado: acceso denegado, sin panel ni sesión abierta.
- Adicional: cuenta desactivada tampoco debe poder acceder.

### PF-02 — Nota fuera de rango

- Paso: ingresar valor inferior/superior a la escala configurada y validada.
- Esperado: mensaje de error, no guardar registro inválido.
- Adicional: impedir valores de texto, registros duplicados y notas en cursos ajenos.

### PF-03 — Cálculo de promedio

- Paso: crear notas ficticias cuyo promedio se conoce con la fórmula aprobada.
- Esperado: mismo valor en la vista de promedios y en el reporte, con redondeo consistente.
- Adicional: comprobar periodos sin registrar.

### PF-04 — Reporte offline

- Paso: detener el acceso a Internet **sin apagar Apache ni MySQL local**; iniciar sesión, abrir el consolidado, imprimir/exportar.
- Esperado: todas las funciones locales siguen disponibles; ningún CSS/JS/fuente depende de recursos externos.

### PF-05 — Respaldo y restauración

- Paso: crear datos ficticios, realizar respaldo, modificar los datos, restaurar y consultar de nuevo.
- Esperado: recuperar exactamente los registros originales y documentar el resultado.
- Realizar en base DEMO, nunca sobrescribir datos reales.

### 17.1. Pruebas técnicas adicionales recomendadas (sin complicar el BTH)

- **T-01:** docente A no ve/edita notas de docente B, incluso usando una URL o petición manual.
- **T-02:** impedir matricular dos veces al mismo estudiante en una gestión conforme a la regla adoptada.
- **T-03:** impedir nota para estudiante de curso diferente al de la asignación.
- **T-04:** impedir periodos con gestión distinta a la asignación.
- **T-05:** impedir borrar registros con notas vinculadas.
- **T-06:** generar reporte consistente con registros de BD y correctamente filtrado por el docente.
- **T-07:** crear cuenta de usuario y verificar que no se almacena contraseña legible.
- **T-08:** comprobar que los respaldos no puedan abrirse desde una URL pública.

No reportar porcentajes de cumplimiento hasta ejecutar las pruebas. El documento exige distinguir **esperado** de **observado**.

---

## 18. Plan de trabajo por etapas (adecuado para dos estudiantes de BTH)

### Etapa 1 — Instalación y base del proyecto

- [ ] Crear aplicación Laravel 12 con Blade y Bootstrap local.
- [ ] Configurar `.env`, XAMPP y base `notas_escolares`.
- [ ] Crear las ocho migraciones y modelos.
- [ ] Ejecutar migraciones sin errores y revisar las FK/índices desde phpMyAdmin.
- [ ] Registrar provider de `Usuario` y middleware `rol`.
- [ ] Crear seeders iniciales ficticios.

**Resultado:** base de datos consistente, entorno local operativo y estructura MVC preparada.

### Etapa 2 — Acceso y administración

- [ ] Login/logout y roles.
- [ ] Plantilla Blade global y panel por rol.
- [ ] CRUD de usuarios.
- [ ] CRUD de estudiantes, cursos y materias.
- [ ] Formularios validados y mensajes.

**Resultado:** administrador puede preparar los datos del colegio en la base local.

### Etapa 3 — Organización académica

- [ ] Matrículas con relaciones correctas.
- [ ] Asignaciones docente/curso/materia.
- [ ] Gestión de periodos.
- [ ] Listado de cursos propios por docente.
- [ ] Confirmar e implementar reglas definitivas de evaluación.

**Resultado:** cada docente tiene asignaciones válidas y estudiantes inscritos.

### Etapa 4 — Notas y promedios

- [ ] Filtros de curso/materia/periodo.
- [ ] Planilla con estudiantes correctos.
- [ ] Guardar y editar calificaciones y observaciones.
- [ ] Proteger contra acceso ajeno y duplicados.
- [ ] Servicio de promedios; comparar con cálculo de referencia.

**Resultado:** docente registra y consulta calificaciones reales de la base DEMO.

### Etapa 5 — Reportes y respaldos

- [ ] Reporte consolidado con filtros.
- [ ] Diseño imprimible y exportación CSV.
- [ ] Crear/listar/descargar respaldos SQL.
- [ ] Restauración protegida y prueba con datos ficticios.
- [ ] Compilar todos los recursos locales de interfaz.

**Resultado:** ciclo completo de notas → reportes → respaldo/recuperación.

### Etapa 6 — Verificación y defensa BTH

- [ ] Ejecutar LC-01 a LC-08 y PF-01 a PF-05.
- [ ] Corregir incidencias y anotar qué cambió.
- [ ] Probar después de **desconectar Internet**.
- [ ] Capturar las pantallas reales para el Anexo N.º 5.
- [ ] Preparar diagrama relacional de ocho tablas para el Anexo N.º 6.
- [ ] Documentar instalación, uso, restauración y requisitos mínimos.
- [ ] Preparar defensa demostrando un flujo completo con datos ficticios.

**Resultado:** proyecto demostrable y evidencia auténtica para el informe escolar.

---

## 19. Documentos complementarios que debe traer el repositorio

### `README.md`

- Nombre y propósito del proyecto.
- Tecnologías y requisito de funcionamiento offline.
- Módulos implementados.
- Cómo arrancar la aplicación en Windows/XAMPP.
- Roles de demostración (sin publicar contraseñas reales).
- Indicación de que es un proyecto BTH de colegio, no plataforma ministerial oficial.

### `docs/MANUAL_INSTALACION.md`

- Versiones usadas de PHP, Laravel, XAMPP/MySQL y navegador.
- Cómo instalar dependencias previamente, configurar `.env`, generar `APP_KEY`, crear base y correr migraciones/seeders.
- Cómo compilar assets y arrancar sin Internet después de preparar el equipo.
- Dónde configurar DocumentRoot `public/`.
- Cómo comprobar acceso desde `localhost` y, si se habilita, desde LAN.
- Cómo solucionar fallas típicas de Apache, MySQL, rutas y permisos de `storage`.

### `docs/MANUAL_USUARIO.md`

- Capturas reales, no bocetos.
- Administrador: iniciar sesión, crear usuarios/estudiantes/cursos/materias, matricular, asignar, crear periodos, generar reporte y respaldar.
- Docente: iniciar sesión, elegir asignación, ingresar nota, consultar promedio y generar reporte.
- Cerrar sesión y recomendaciones de resguardo.

### `docs/PRUEBAS_BTH.md`

- Lista LC y pruebas PF completas.
- Datos ficticios de prueba.
- Resultado esperado y observado.
- Evidencia con fecha y capturas.
- Incidencias y correcciones, sin inventar resultados.

### `docs/REGLAS_ACADEMICAS.md`

- Fuente normativa consultada y fecha.
- Escala, criterios, periodos y forma de promedio **que realmente aplique**.
- Decisiones adoptadas con el docente guía.
- Cómo se refleja cada regla en `config/academico.php` y `CalculoPromediosService`.

---

## 20. Cosas que NO deben añadirse innecesariamente

- No crear un ERP de colegios, gestión financiera, pagos, transporte o inventario.
- No crear módulos de asistencia, disciplina ni mensajería porque no aparecen en los nueve módulos del documento.
- No desarrollar aplicación Android/iOS.
- No montar Firebase, Supabase, AWS ni servicios de Internet para funcionalidades básicas.
- No cambiar Blade/Bootstrap por React/Next.js/Vue si el objetivo es seguir el documento.
- No instalar cinco paquetes de arquitectura, microservicios, colas, web sockets o sistemas multiempresa.
- No exigir correo electrónico, SMS o recuperación mediante Internet.
- No crear accesos de alumnos, padres o autoridades sin aprobación institucional.
- No inventar equivalencias, ponderaciones, escalas ni promedios bolivianos.
- No guardar promedios manuales duplicados que puedan quedar diferentes de las notas.
- No entregar solo mockups: **cada botón mostrado debe funcionar o retirarse de la versión final**.

---

## 21. Puntos por resolver con el colegio/docente guía

Marcar como **pendientes de definición institucional**, no como defectos del documento ni como funciones ya implementadas:

- [ ] **Regla de calificaciones vigente en 2026:** rango, decimales, escala, redondeo y ponderaciones de evaluación.
- [ ] **Periodos:** nombres y cantidad aplicable al nivel/cursos atendidos.
- [ ] **Promedio:** cómo tratar periodos incompletos y cómo se considera el resultado final.
- [ ] **Una matrícula por gestión:** confirmar qué ocurre con traslado de curso dentro del año.
- [ ] **Docente único por curso/materia/gestión:** confirmar si se permite codocencia o suplencias.
- [ ] **Notas anuladas/corregidas:** definir si basta editar o hace falta registrar motivo; para BTH básico basta corregir con permiso docente y confirmación.
- [ ] **Quién puede ver reportes generales:** administrador y docentes según asignaciones; ampliar solo si se autoriza.
- [ ] **Instalación:** una computadora local o acceso por LAN, según recursos y permisos reales.

### Base normativa citada por el documento de proyecto

- Reglamento de Evaluación del Desarrollo Curricular del Subsistema de Educación Regular, **R.M. N.º 0190/2024**.
- Normas generales de la gestión educativa 2026, **R.M. N.º 0001/2026**.

**No se verificó aquí el contenido vigente de estas normas en fuentes externas.** El documento de origen pide consultar las disposiciones aplicables y sus posibles modificaciones antes de programar la evaluación. Esta especificación respeta expresamente esa limitación.

---

## 22. Criterios de aceptación final: ¿cuándo está HECHO?

El proyecto se considera técnicamente listo para presentar como BTH cuando:

1. [ ] Se instala e inicia de nuevo desde Windows/XAMPP con instrucciones reproducibles.
2. [ ] Existen las **ocho tablas académicas** con relaciones e índices correctos.
3. [ ] Funciona el inicio y cierre de sesión con administrador y docente.
4. [ ] El administrador puede registrar datos de estudiantes, cursos, materias, matrículas, asignaciones y periodos.
5. [ ] El docente ve únicamente sus asignaciones y estudiantes correspondientes.
6. [ ] Las notas se guardan, consultan y modifican en MySQL/MariaDB con validación y sin duplicados.
7. [ ] Los promedios utilizan una regla documentada, comprobada y consistente entre todas las pantallas.
8. [ ] Los reportes se ven, imprimen y exportan **sin depender de Internet**.
9. [ ] El administrador crea un respaldo y recupera correctamente datos ficticios.
10. [ ] La interfaz usa recursos locales; desconectar Internet no impide operar.
11. [ ] LC-01 a LC-08 y PF-01 a PF-05 tienen resultados observados y evidencias auténticas.
12. [ ] Los manuales y diagramas están listos para los anexos y la defensa.
13. [ ] No hay contraseñas de demostración expuestas ni datos privados reales de estudiantes.
14. [ ] No existen enlaces o botones principales sin implementar.

### Guion corto para la demostración final

1. Iniciar XAMPP y abrir el sitio local con Internet desconectado.
2. Ingresar como administrador; mostrar alumnos, curso, materia, periodo y docente asignado.
3. Salir e ingresar como docente; escoger una asignación propia.
4. Introducir dos o más calificaciones ficticias y observaciones; guardar.
5. Consultar promedio y generar reporte impreso/CSV.
6. Demostrar que el docente no entra a administración ni cambia notas de otro docente.
7. Volver a administrador, crear respaldo y mostrar el procedimiento de recuperación con datos DEMO.
8. Enseñar los casos de prueba y explicar la relación de las ocho tablas.

---

## 23. Instrucción directa para un asistente de programación / Codex

> Implementa esta especificación como una aplicación completa en Laravel 12, Blade, Bootstrap 5.3 y MySQL/MariaDB ejecutable con XAMPP en Windows sin conexión a Internet. Respeta las ocho tablas del Anexo 6 del documento BTH y los nueve módulos funcionales. Crea los archivos de modelos, migraciones, controladores, Form Requests, middleware, servicios, rutas, vistas, seeders, pruebas y manuales indicados. Usa autenticación por nombre de usuario, roles administrador/docente y restricciones por asignación. No agregues funciones ajenas al alcance escolar. No inventes reglas bolivianas de evaluación; deja configurable y documenta las decisiones pendientes. Construye cada módulo conectado a la base real, no como interfaz simulada. Trabaja por etapas, ejecuta migraciones y pruebas, corrige fallos y entrega evidencias. Los respaldos deben ser recuperables, y todo el sistema debe continuar operando sin Internet tras instalar dependencias y compilar recursos.

---

### Trazabilidad al documento fuente

- **Resumen, pp. 5:** tecnologías, offline y módulos previstos.
- **Objetivos específicos, pp. 12–13:** módulos Laravel 12 y pruebas con datos ficticios.
- **Marco de datos, p. 17:** relaciones y restricciones para evitar duplicados.
- **Diseño, pp. 19–21:** pantallas A–F, acciones y navegación; nueve componentes.
- **Calidad, p. 22:** permisos, notas válidas, promedios, reportes, respaldo/offline.
- **Metodología, p. 28:** lista de cotejo, matriz de procesos y PF-01 a PF-05.
- **Proyección, pp. 29–30:** estadísticas y libretas como posibles ampliaciones futuras.
- **Anexos, pp. 37–39:** LC-01 a LC-08, PF-01 a PF-05 y estructura de las ocho tablas.

**Estado de esta entrega:** documento de especificación técnica elaborado; desarrollo de software y ejecución de pruebas **pendientes**.
