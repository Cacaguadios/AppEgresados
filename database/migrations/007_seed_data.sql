-- =====================================================
-- SEED 007: Datos realistas para pruebas de flujo completo
-- Ofertas de TI aprobadas, pendientes, rechazadas
-- Postulaciones, perfiles de egresados y notificaciones
-- =====================================================

-- ─── 1. Actualizar perfiles de egresados con datos completos ───

UPDATE egresados SET
  correo_personal = 'carlos.hdez@gmail.com',
  telefono = '2221234567',
  especialidad = 'Desarrollo de Software',
  generacion = 2024,
  habilidades = '["React","Node.js","JavaScript","MySQL","Git","HTML","CSS"]',
  trabaja_actualmente = 0,
  genero = 'M',
  año_nacimiento = 2001
WHERE id_usuario = 4;

UPDATE egresados SET
  correo_personal = 'mauricio.pop@outlook.com',
  telefono = '2229876543',
  especialidad = 'Desarrollo de Software',
  generacion = 2023,
  habilidades = '["Python","Django","PostgreSQL","Docker","Linux","AWS"]',
  trabaja_actualmente = 1,
  trabaja_en_ti = 1,
  empresa_actual = 'Softtek',
  puesto_actual = 'Desarrollador Backend',
  modalidad_trabajo = 'remoto',
  genero = 'M',
  año_nacimiento = 2000
WHERE id_usuario = 6;

UPDATE egresados SET
  correo_personal = 'omar.anzures@gmail.com',
  telefono = '2225551234',
  especialidad = 'Redes y Telecomunicaciones',
  generacion = 2023,
  habilidades = '["Cisco","Mikrotik","Windows Server","Active Directory","Firewall","VPN"]',
  trabaja_actualmente = 1,
  trabaja_en_ti = 1,
  empresa_actual = 'Telmex',
  puesto_actual = 'Ingeniero de Redes',
  modalidad_trabajo = 'presencial',
  genero = 'M',
  año_nacimiento = 1999
WHERE id_usuario = 7;

UPDATE egresados SET
  correo_personal = 'jose.hdez.dev@gmail.com',
  telefono = '2228887766',
  especialidad = 'Desarrollo de Software',
  generacion = 2024,
  habilidades = '["Java","Spring Boot","Angular","TypeScript","MongoDB","Jenkins"]',
  trabaja_actualmente = 0,
  genero = 'M',
  año_nacimiento = 2001
WHERE id_usuario = 8;

UPDATE egresados SET
  correo_personal = 'carlos.cuaya@hotmail.com',
  telefono = '2223334455',
  especialidad = 'Inteligencia Artificial',
  generacion = 2025,
  habilidades = '["Python","TensorFlow","PyTorch","Pandas","Scikit-learn","SQL","R"]',
  trabaja_actualmente = 0,
  genero = 'M',
  año_nacimiento = 2002
WHERE id_usuario = 11;

-- ─── 2. Nuevas ofertas APROBADAS (creadas por docentes/TI existentes) ───

INSERT INTO ofertas (id_usuario_creador, titulo, empresa, ubicacion, modalidad, jornada, salario_min, salario_max, beneficios, habilidades, descripcion, requisitos, contacto, estado, estado_vacante, vacantes, especialidad_requerida, experiencia_minima, fecha_creacion, fecha_expiracion, fecha_aprobacion, id_admin_aprobador) VALUES

-- Oferta 6: Backend Python (creada por Pedro García, docente)
(12, 'Desarrollador Backend Python', 'Wizeline México', 'Guadalajara, Jalisco', 'remoto', 'completo',
 22000.00, 32000.00,
 '["Seguro de gastos médicos mayores y menores","Vales de despensa $2,500/mes","Bono anual de desempeño","Presupuesto para home office","Días ilimitados de vacaciones"]',
 '["Python","Django","FastAPI","PostgreSQL","Redis","Docker","Git"]',
 'Wizeline busca desarrollador backend con experiencia en Python para unirse al equipo que construye plataformas de e-commerce de alto tráfico. Trabajarás con arquitecturas de microservicios, APIs RESTful y bases de datos distribuidas. Ambiente internacional con equipos en LATAM y USA.',
 '["Ingeniería en Sistemas, Computación o afín","2+ años de experiencia con Python y Django/FastAPI","Conocimientos sólidos de SQL y bases de datos relacionales","Experiencia con Docker y CI/CD","Inglés intermedio-avanzado"]',
 'careers@wizeline.com', 'aprobada', 'verde', 2, 'Desarrollo de Software', 2,
 '2026-02-10 09:00:00', '2026-04-10 00:00:00', '2026-02-12 10:30:00', 1),

-- Oferta 7: Ciberseguridad (creada por Carlos López, TI)
(13, 'Analista de Ciberseguridad Jr', 'BBVA México', 'Ciudad de México', 'hibrido', 'completo',
 20000.00, 28000.00,
 '["Seguro de gastos médicos","Fondo de ahorro 13%","Vales de despensa","Programa de bienestar","Descuentos en productos financieros"]',
 '["Seguridad Informática","SIEM","Firewall","Linux","Networking","Ethical Hacking"]',
 'BBVA busca analista de ciberseguridad para monitorear y responder a incidentes de seguridad informática. Participarás en la implementación de controles de seguridad, análisis de vulnerabilidades y respuesta ante incidentes en un entorno bancario regulado.',
 '["Ingeniería en Sistemas, Redes o Ciberseguridad","Conocimientos de redes TCP/IP y firewalls","Familiaridad con herramientas SIEM","Certificación CompTIA Security+ deseable","Disponibilidad para horarios rotativos"]',
 'talento.ti@bbva.mx', 'aprobada', 'verde', 3, 'Redes y Telecomunicaciones', 0,
 '2026-02-08 14:00:00', '2026-03-28 00:00:00', '2026-02-10 09:00:00', 1),

-- Oferta 8: Mobile Developer (creada por María López, docente)
(5, 'Desarrollador Mobile React Native', 'Rappi', 'Ciudad de México', 'hibrido', 'completo',
 25000.00, 38000.00,
 '["Stock options","Seguro de gastos médicos","Crédito Rappi mensual","Snacks ilimitados","Gym en la oficina"]',
 '["React Native","TypeScript","JavaScript","Redux","Firebase","Git","REST APIs"]',
 'Rappi está buscando desarrolladores mobile para crear experiencias excepcionales en su app utilizada por millones de usuarios en LATAM. Trabajarás en features de alto impacto con un equipo ágil y multidisciplinario.',
 '["Ingeniería en Sistemas o afín","1+ año de experiencia con React Native","Conocimientos de TypeScript y Redux","Experiencia publicando apps en App Store / Play Store","Trabajo en equipo y metodologías ágiles"]',
 'mobile-hiring@rappi.com', 'aprobada', 'verde', 2, 'Desarrollo de Software', 1,
 '2026-02-05 11:00:00', '2026-04-05 00:00:00', '2026-02-07 15:00:00', 1),

-- Oferta 9: Data Science (creada por Pedro García, docente)
(12, 'Data Scientist', 'Mercado Libre', 'Remoto desde México', 'remoto', 'completo',
 30000.00, 45000.00,
 '["Trabajo 100% remoto","ESOP (acciones de la empresa)","Seguro médico premium","Presupuesto para capacitación $30,000/año","Horario flexible"]',
 '["Python","Machine Learning","TensorFlow","SQL","Pandas","Spark","Estadística"]',
 'Mercado Libre busca Data Scientist para su equipo de Pricing & Revenue. Desarrollarás modelos de machine learning para optimizar precios, predecir demanda y personalizar la experiencia de millones de usuarios en la plataforma.',
 '["Maestría o Licenciatura en Ciencia de Datos, Estadística, Matemáticas o afín","Experiencia con Python, Pandas y Scikit-learn","Conocimientos de modelos de ML supervisados y no supervisados","Experiencia con SQL y grandes volúmenes de datos","Comunicación efectiva de insights"]',
 'ds-careers@mercadolibre.com', 'aprobada', 'verde', 1, 'Inteligencia Artificial', 1,
 '2026-02-14 08:00:00', '2026-04-15 00:00:00', '2026-02-16 10:00:00', 1),

-- Oferta 10: QA Automation (creada por Carlos López, TI)
(13, 'QA Automation Engineer', 'Globant', 'Puebla, Puebla', 'hibrido', 'completo',
 18000.00, 26000.00,
 '["Seguro de gastos médicos","Star Program (reconocimientos)","Capacitación en Globant University","Horario flexible","Viernes corto"]',
 '["Selenium","Cypress","JavaScript","Java","API Testing","Postman","Jira"]',
 'Globant busca QA Automation Engineer para asegurar la calidad del software en proyectos para clientes Fortune 500. Diseñarás e implementarás frameworks de automatización, escribirás pruebas E2E y colaborarás con equipos de desarrollo.',
 '["Ingeniería en Sistemas o afín","Experiencia con Selenium o Cypress","Conocimientos de JavaScript o Java","Experiencia con testing de APIs (Postman/RestAssured)","Metodologías ágiles (Scrum)"]',
 'hiring.mx@globant.com', 'aprobada', 'amarillo', 2, 'Desarrollo de Software', 1,
 '2026-02-12 10:00:00', '2026-03-30 00:00:00', '2026-02-14 11:30:00', 1),

-- Oferta 11: Infraestructura Cloud (creada por Pedro García, docente)
(12, 'Ingeniero de Infraestructura Cloud', 'Amazon Web Services', 'Ciudad de México', 'hibrido', 'completo',
 35000.00, 55000.00,
 '["RSUs (acciones Amazon)","Seguro médico premium global","Relocation assistance","Capacitación continua AWS","Beneficios de Amazon empleados"]',
 '["AWS","Terraform","Linux","Python","Networking","CloudFormation","Kubernetes"]',
 'AWS busca ingeniero de infraestructura para su equipo de Professional Services en México. Ayudarás a clientes enterprise a migrar y optimizar su infraestructura en la nube, diseñando soluciones de alta disponibilidad y escalabilidad.',
 '["Ingeniería en Sistemas, Redes o afín","Certificación AWS Solutions Architect o equivalente","Experiencia con IaC (Terraform/CloudFormation)","Conocimientos avanzados de Linux y networking","Inglés avanzado (ambiente internacional)","3+ años de experiencia en infraestructura"]',
 'aws-mx-hiring@amazon.com', 'aprobada', 'verde', 2, 'Redes y Telecomunicaciones', 3,
 '2026-02-18 09:00:00', '2026-05-01 00:00:00', '2026-02-20 08:30:00', 1);

-- ─── 3. Ofertas PENDIENTES DE APROBACIÓN ───

INSERT INTO ofertas (id_usuario_creador, titulo, empresa, ubicacion, modalidad, jornada, salario_min, salario_max, beneficios, habilidades, descripcion, requisitos, contacto, estado, estado_vacante, vacantes, especialidad_requerida, experiencia_minima, fecha_creacion, fecha_expiracion) VALUES

-- Pendiente 1: Frontend dev
(12, 'Desarrollador Frontend Angular', 'Accenture México', 'Puebla, Puebla', 'hibrido', 'completo',
 17000.00, 24000.00,
 '["Seguro médico","Vales de despensa","Academia Accenture","Certificaciones pagadas"]',
 '["Angular","TypeScript","RxJS","HTML","CSS","SASS","Git"]',
 'Accenture busca desarrollador frontend con experiencia en Angular para proyectos de banca digital. Crearás interfaces responsivas, trabajarás con APIs REST y colaborarás con equipos distribuidos globalmente.',
 '["Ingeniería en Sistemas o afín","1+ año con Angular 12+","Conocimientos de TypeScript y RxJS","Experiencia con control de versiones Git","Deseable: inglés intermedio"]',
 'reclutamiento.mx@accenture.com', 'pendiente_aprobacion', 'verde', 3, 'Desarrollo de Software', 1,
 '2026-02-22 10:00:00', '2026-04-22 00:00:00'),

-- Pendiente 2: Administrador de redes
(13, 'Administrador de Redes y Servidores', 'Hospital Ángeles Puebla', 'Puebla, Puebla', 'presencial', 'completo',
 14000.00, 19000.00,
 '["Prestaciones superiores a la ley","Seguro de vida","Comedor subsidiado","Estacionamiento"]',
 '["Windows Server","Active Directory","VMware","Cisco","DHCP","DNS","VPN"]',
 'Hospital Ángeles requiere administrador de redes para gestionar la infraestructura tecnológica del hospital. Responsable de servidores, redes, VPN para consultas remotas y soporte técnico a áreas médicas.',
 '["TSU o Ingeniería en Redes o Telecomunicaciones","Certificación CCNA deseable","Experiencia con Windows Server y virtualización","Disponibilidad para guardias","Conocimientos de normatividad hospitalaria en TI"]',
 'rh@angelespuebla.com.mx', 'pendiente_aprobacion', 'verde', 1, 'Redes y Telecomunicaciones', 1,
 '2026-02-23 08:30:00', '2026-04-15 00:00:00'),

-- Pendiente 3: AI/ML Engineer
(5, 'Ingeniero de Machine Learning', 'Kavak', 'Ciudad de México', 'remoto', 'completo',
 28000.00, 42000.00,
 '["Trabajo remoto","Seguro médico","Descuento en autos Kavak","Stock options","Presupuesto para equipo"]',
 '["Python","TensorFlow","PyTorch","MLOps","Docker","Kubernetes","SQL"]',
 'Kavak busca ML Engineer para desarrollar modelos de pricing de vehículos, detección de fraude y recomendaciones. Trabajarás en el ciclo completo de ML: desde la experimentación hasta el deployment en producción.',
 '["Maestría en Ciencia de Datos, IA o afín","Experiencia con TensorFlow o PyTorch","Conocimientos de MLOps y deployment de modelos","Experiencia con Docker y Kubernetes","Python avanzado"]',
 'ml-team@kavak.com', 'pendiente_aprobacion', 'verde', 2, 'Inteligencia Artificial', 2,
 '2026-02-24 09:00:00', '2026-04-30 00:00:00');

-- ─── 4. Una oferta RECHAZADA (para mostrar variedad) ───

INSERT INTO ofertas (id_usuario_creador, titulo, empresa, ubicacion, modalidad, jornada, salario_min, salario_max, beneficios, habilidades, descripcion, requisitos, contacto, estado, estado_vacante, vacantes, especialidad_requerida, experiencia_minima, fecha_creacion, fecha_expiracion, razon_rechazo) VALUES
(13, 'Técnico de Soporte (medio tiempo)', 'Cyber Café Digital', 'Puebla, Puebla', 'presencial', 'parcial',
 4000.00, 6000.00,
 '["Horario flexible"]',
 '["Windows","Hardware","Impresoras"]',
 'Se busca técnico de soporte para atender clientes en cyber café. Actividades: mantenimiento de equipos, instalación de software e impresión de documentos.',
 '["Conocimientos básicos de computación","Disponibilidad medio tiempo"]',
 'contacto@cyberdigital.mx', 'rechazada', 'verde', 1, NULL, 0,
 '2026-02-20 15:00:00', '2026-03-20 00:00:00',
 'La oferta no cumple con los estándares mínimos de salario y condiciones laborales para nuestros egresados. Se sugiere mejorar la propuesta salarial y beneficios.');

-- ─── 5. Postulaciones adicionales para generar flujo ───

-- Mauricio (egresado id=4) se postula a Python Backend
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(6, 4, '2026-02-13 14:00:00', 'preseleccionado', 'cumple');

-- Mauricio se postula a Data Science
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(9, 4, '2026-02-17 11:30:00', 'pendiente', 'cumple');

-- Omar (egresado id=5) se postula a Ciberseguridad
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(7, 5, '2026-02-11 09:45:00', 'contactado', 'cumple');

-- Omar se postula a Infra Cloud
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(11, 5, '2026-02-21 16:00:00', 'pendiente', 'cumple');

-- Jose (egresado id=6) se postula a QA Automation
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(10, 6, '2026-02-15 10:20:00', 'preseleccionado', 'cumple');

-- Jose se postula a Mobile Developer
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(8, 6, '2026-02-08 13:00:00', 'pendiente', 'no_cumple');

-- Carlos Cuaya (egresado id=9) se postula a Data Science
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(9, 9, '2026-02-18 08:30:00', 'pendiente', 'cumple');

-- Carlos Cuaya se postula a QA Automation
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(10, 9, '2026-02-16 15:45:00', 'pendiente', 'cumple');

-- test.egresado (egresado id=3) se postula a Python Backend (nueva)
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(6, 3, '2026-02-14 11:00:00', 'pendiente', 'cumple');

-- test.egresado se postula a Ciberseguridad
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(7, 3, '2026-02-12 08:00:00', 'rechazado', 'no_cumple');

-- Juan Pérez (egresado id=2) se postula a Mobile Dev
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(8, 2, '2026-02-09 10:30:00', 'preseleccionado', 'cumple');

-- Juan Pérez se postula a Infra Cloud
INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica) VALUES
(11, 2, '2026-02-22 09:15:00', 'pendiente', 'cumple');

-- ─── 6. Actualizar estado_vacante de ofertas con postulantes ───

UPDATE ofertas SET estado_vacante = 'amarillo' WHERE id = 6;
UPDATE ofertas SET estado_vacante = 'amarillo' WHERE id = 7;
UPDATE ofertas SET estado_vacante = 'amarillo' WHERE id = 8;
UPDATE ofertas SET estado_vacante = 'amarillo' WHERE id = 9;
-- oferta 10 ya está en amarillo
UPDATE ofertas SET estado_vacante = 'amarillo' WHERE id = 11;

-- ─── 7. Notificaciones para todos los roles ───

-- Notificaciones para ADMIN (id=1)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(1, 'nueva_postulacion', 'Nueva oferta por moderar', 'Pedro García publicó la oferta "Desarrollador Frontend Angular". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 0, '2026-02-22 10:01:00'),
(1, 'nueva_postulacion', 'Nueva oferta por moderar', 'Carlos López publicó la oferta "Administrador de Redes y Servidores". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 0, '2026-02-23 08:31:00'),
(1, 'nueva_postulacion', 'Nueva oferta por moderar', 'María López publicó la oferta "Ingeniero de Machine Learning". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 0, '2026-02-24 09:01:00'),
(1, 'nueva_postulacion', 'Nueva oferta por moderar', 'Carlos López publicó la oferta "Técnico de Soporte (medio tiempo)". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 1, '2026-02-20 15:01:00');

-- Notificaciones para DOCENTE María López (id=5)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(5, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Desarrollador Mobile React Native" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-07 15:01:00'),
(5, 'nueva_postulacion', 'Nuevo postulante', 'Jose Hernandez se postuló a tu oferta "Desarrollador Mobile React Native".', '../../views/docente/postulantes.php', 0, '2026-02-08 13:01:00'),
(5, 'nueva_postulacion', 'Nuevo postulante', 'Juan Pérez García se postuló a tu oferta "Desarrollador Mobile React Native".', '../../views/docente/postulantes.php', 0, '2026-02-09 10:31:00'),
(5, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Desarrollador Full Stack Junior" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-01-16 10:01:00'),
(5, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Hernández se postuló a tu oferta "Desarrollador Full Stack Junior".', '../../views/docente/postulantes.php', 1, '2026-01-20 14:31:00');

-- Notificaciones para DOCENTE Pedro García (id=12)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(12, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Desarrollador Backend Python" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-12 10:31:00'),
(12, 'nueva_postulacion', 'Nuevo postulante', 'Mauricio Popoca se postuló a tu oferta "Desarrollador Backend Python".', '../../views/docente/postulantes.php', 0, '2026-02-13 14:01:00'),
(12, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Hernández se postuló a tu oferta "Desarrollador Backend Python".', '../../views/docente/postulantes.php', 0, '2026-02-14 11:01:00'),
(12, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Data Scientist" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 0, '2026-02-16 10:01:00'),
(12, 'nueva_postulacion', 'Nuevo postulante', 'Mauricio Popoca se postuló a tu oferta "Data Scientist".', '../../views/docente/postulantes.php', 0, '2026-02-17 11:31:00'),
(12, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Cuaya se postuló a tu oferta "Data Scientist".', '../../views/docente/postulantes.php', 0, '2026-02-18 08:31:00'),
(12, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Ingeniero de Infraestructura Cloud" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 0, '2026-02-20 08:31:00'),
(12, 'nueva_postulacion', 'Nuevo postulante', 'Omar Anzures Campos se postuló a tu oferta "Ingeniero de Infraestructura Cloud".', '../../views/docente/postulantes.php', 0, '2026-02-21 16:01:00'),
(12, 'nueva_postulacion', 'Nuevo postulante', 'Juan Pérez García se postuló a tu oferta "Ingeniero de Infraestructura Cloud".', '../../views/docente/postulantes.php', 0, '2026-02-22 09:16:00');

-- Notificaciones para TI Carlos López (id=13)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(13, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Analista de Ciberseguridad Jr" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-10 09:01:00'),
(13, 'nueva_postulacion', 'Nuevo postulante', 'Omar Anzures Campos se postuló a tu oferta "Analista de Ciberseguridad Jr".', '../../views/docente/postulantes.php', 0, '2026-02-11 09:46:00'),
(13, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Hernández se postuló a tu oferta "Analista de Ciberseguridad Jr".', '../../views/docente/postulantes.php', 0, '2026-02-12 08:01:00'),
(13, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "QA Automation Engineer" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-14 11:31:00'),
(13, 'nueva_postulacion', 'Nuevo postulante', 'Jose Hernandez se postuló a tu oferta "QA Automation Engineer".', '../../views/docente/postulantes.php', 0, '2026-02-15 10:21:00'),
(13, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Cuaya se postuló a tu oferta "QA Automation Engineer".', '../../views/docente/postulantes.php', 0, '2026-02-16 15:46:00'),
(13, 'oferta_rechazada', 'Oferta rechazada', 'La oferta "Técnico de Soporte (medio tiempo)" fue rechazada. Motivo: La oferta no cumple con los estándares mínimos de salario y condiciones laborales.', '../../views/docente/mis-ofertas.php', 0, '2026-02-21 09:00:00');

-- Notificaciones para EGRESADOS (ofertas nuevas aprobadas)

-- test.egresado (id=4) - varias notificaciones de ofertas nuevas
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Backend Python". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=6', 1, '2026-02-12 10:31:00'),
(4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Analista de Ciberseguridad Jr". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=7', 1, '2026-02-10 09:01:00'),
(4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Mobile React Native". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=8', 1, '2026-02-07 15:01:00'),
(4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Data Scientist". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=9', 0, '2026-02-16 10:01:00'),
(4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "QA Automation Engineer". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=10', 0, '2026-02-14 11:31:00'),
(4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00'),
(4, 'postulacion_rechazada', 'Postulación no seleccionada', 'Tu postulación para "Analista de Ciberseguridad Jr" no fue seleccionada en esta ocasión.', '../../views/egresado/postulaciones.php', 0, '2026-02-13 10:00:00');

-- Mauricio (id=6)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(6, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Backend Python". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=6', 1, '2026-02-12 10:31:00'),
(6, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Data Scientist". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=9', 0, '2026-02-16 10:01:00'),
(6, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00');

-- Omar (id=7)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(7, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Analista de Ciberseguridad Jr". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=7', 1, '2026-02-10 09:01:00'),
(7, 'postulacion_seleccionada', '¡Has sido seleccionado!', '¡Felicidades! Fuiste seleccionado para la oferta "Analista de Ciberseguridad Jr".', '../../views/egresado/postulaciones.php', 0, '2026-02-15 14:00:00'),
(7, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00');

-- Jose (id=8)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(8, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "QA Automation Engineer". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=10', 0, '2026-02-14 11:31:00'),
(8, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Mobile React Native". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=8', 0, '2026-02-07 15:01:00');

-- Carlos Cuaya (id=11)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(11, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Data Scientist". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=9', 1, '2026-02-16 10:01:00'),
(11, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "QA Automation Engineer". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=10', 0, '2026-02-14 11:31:00'),
(11, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00');

-- Juan Pérez (id=3)
INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES
(3, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Mobile React Native". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=8', 1, '2026-02-07 15:01:00'),
(3, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00');
