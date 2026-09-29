{{-- Politica de privacidad, en español. Los datos de la empresa vienen del controlador. --}}
<h2>1. Quién trata sus datos</h2>
<p>El responsable del tratamiento es <strong>{{ $responsable }}</strong>, titular de la plataforma {{ $marca }}{{ $domicilio ? ', con domicilio en '.$domicilio : '' }}{{ $registro ? ' ('.$registro.')' : '' }}. Puede escribirnos para cualquier cuestión sobre sus datos a <a href="mailto:{{ $correo }}">{{ $correo }}</a>.</p>
<p>Esta política se aplica a quienes crean una cuenta en {{ $marca }} (promotoras, agentes y su equipo), a quienes visitan un proyecto publicado o escriben a través de él (compradores interesados) y a quienes navegan por el portal.</p>

<h2>2. Qué datos tratamos y para qué</h2>
<ul>
    <li><strong>Cuenta de promotora o agente:</strong> nombre, correo, contraseña cifrada, empresa, teléfono, país y ciudad. Para prestar el servicio, gestionar su suscripción, avisarle de lo que pasa en su cuenta y atender sus consultas.</li>
    <li><strong>Facturación:</strong> los pagos los procesa Stripe. Nosotros guardamos el identificador de cliente, el plan y el estado de la suscripción; nunca el número completo de la tarjeta.</li>
    <li><strong>Consultas de compradores:</strong> nombre, correo, teléfono y el mensaje que escribe en el formulario o en el asistente de un proyecto. Se guardan para entregarlos a la promotora del proyecto, que es quien le responde, y para enviarle a usted un acuse de recibo.</li>
    <li><strong>Conversaciones con el asistente:</strong> los mensajes que escribe al asistente de un proyecto se procesan con un proveedor de inteligencia artificial para generar la respuesta, y se guardan junto a la consulta si decide dejar su contacto. El asistente no toma decisiones con efectos jurídicos sobre usted.</li>
    <li><strong>Uso del visor y del portal:</strong> páginas y viviendas vistas, tiempo en el visor y tipo de dispositivo, asociados a un identificador de sesión, no a su identidad. Sirven para que la promotora sepa qué interesa de su proyecto y para mejorar el servicio.</li>
    <li><strong>Registros técnicos:</strong> dirección IP, navegador y fecha de cada petición, durante un tiempo limitado, para la seguridad del servicio y para limitar abusos.</li>
</ul>

<h2>3. Con qué base</h2>
<p>Tratamos los datos de la cuenta y de facturación porque son necesarios para el contrato que acepta al registrarse. Las consultas de compradores se tratan por su propia solicitud de ser contactado. Los registros técnicos y las medidas contra abusos se apoyan en nuestro interés legítimo en mantener el servicio seguro. Las cookies analíticas, cuando existen, solo se instalan con su consentimiento (véase la política de cookies).</p>

<h2>4. Con quién compartimos datos</h2>
<p>Con la <strong>promotora del proyecto</strong> al que escribe, que recibe su consulta y es responsable de cómo la atiende. Y con los proveedores que necesitamos para prestar el servicio, que tratan los datos por nuestra cuenta y bajo contrato: el proveedor de alojamiento donde corre la plataforma, Stripe para los pagos, el proveedor de envío de correo para las notificaciones y el proveedor de inteligencia artificial que genera las respuestas del asistente. No vendemos datos ni los cedemos a terceros para su propia publicidad.</p>
<p>Alguno de estos proveedores puede estar fuera de su país. Cuando es así, exigimos garantías adecuadas (cláusulas contractuales tipo o mecanismos equivalentes).</p>

<h2>5. Cuánto tiempo los guardamos</h2>
<p>Los datos de la cuenta, mientras la cuenta exista y, después, el tiempo que la ley fiscal y mercantil obliga a conservar la facturación. Las consultas de compradores, mientras el proyecto siga publicado y hasta dos años después de la última actividad, salvo que la promotora o usted pidan borrarlas antes. Los registros técnicos, como máximo doce meses.</p>

<h2>6. Sus derechos</h2>
<p>Puede acceder a sus datos, corregirlos, pedir que los borremos, oponerse a un tratamiento, limitarlo o llevárselos en un formato reutilizable. Si tiene cuenta, puede exportar y eliminar sus datos desde el propio panel. En cualquier otro caso, escriba a <a href="mailto:{{ $correo }}">{{ $correo }}</a> y le contestaremos en el plazo legal.</p>
<p>Si es residente en la República Dominicana, le amparan la Ley 172-13 de protección de datos de carácter personal y la autoridad competente en la materia. Si es residente en la Unión Europea, le ampara el Reglamento (UE) 2016/679 y puede reclamar ante la autoridad de control de su país (en España, la Agencia Española de Protección de Datos).</p>

<h2>7. Seguridad</h2>
<p>La plataforma se sirve cifrada (HTTPS), las contraseñas se guardan con un algoritmo de un solo sentido, el acceso a los servidores está restringido y se hacen copias de seguridad diarias cuya restauración se comprueba. Ningún sistema es infalible: si detectamos una brecha que afecte a sus datos, se lo comunicaremos como exige la ley.</p>

<h2>8. Cambios</h2>
<p>Si cambiamos esta política de forma sustancial, se lo diremos en el panel o por correo y le pediremos que acepte de nuevo la versión vigente. La fecha de la versión aparece al principio de esta página.</p>
