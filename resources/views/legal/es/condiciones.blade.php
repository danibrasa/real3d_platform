{{-- Condiciones del servicio, en español. --}}
<h2>1. Quiénes somos y qué es el servicio</h2>
<p>{{ $marca }} es una plataforma de <strong>{{ $responsable }}</strong>{{ $domicilio ? ', con domicilio en '.$domicilio : '' }}, con la que una promotora inmobiliaria publica sus proyectos en un visor 3D con fichas de vivienda, recibe las consultas de los compradores interesados y las gestiona desde un panel. Al crear una cuenta acepta estas condiciones; si actúa en nombre de una empresa, declara tener poder para obligarla.</p>

<h2>2. La cuenta</h2>
<p>La cuenta es de la empresa que la crea. Usted es responsable de la contraseña y de lo que se haga con ella; avísenos en cuanto sospeche un acceso no autorizado. Puede invitar a agentes de su equipo dentro de los límites de su plan; responde de lo que hagan en la plataforma.</p>

<h2>3. Planes, prueba y pago</h2>
<p>Los planes y sus precios están publicados en la plataforma. El plan gratuito incluye lo que allí se indica; los planes de pago se facturan por adelantado, mensual o anualmente, a través de Stripe, y se renuevan solos hasta que los cancele.</p>
<p>El periodo de prueba de un plan de pago empieza cuando su visor se publica, no cuando se registra: así no consume prueba mientras preparamos su proyecto. Al terminar la prueba se cobra el plan elegido salvo que cancele antes. Si un cobro falla, se lo comunicaremos y reintentaremos; si sigue fallando, la cuenta vuelve al plan gratuito y el visor 3D deja de servirse, pero su ficha pública, sus viviendas y sus consultas se conservan.</p>
<p>Puede cambiar de plan o cancelar en cualquier momento desde el panel. Al cancelar, el plan de pago sigue activo hasta el final del periodo ya pagado. No hay reembolsos por periodos parciales, salvo que la ley aplicable disponga otra cosa.</p>

<h2>4. El montaje del visor</h2>
<p>El visor 3D lo monta nuestro equipo a partir del material que usted nos entrega: planos, renders, imágenes o vídeo 360 y, si lo tiene, el modelo tridimensional. Usted garantiza que tiene derecho a usar ese material y nos autoriza a reproducirlo y adaptarlo para publicarlo en su proyecto. Nos comprometemos a montarlo en un plazo razonable, que le comunicamos al recibir el material, y a no publicarlo sin su visto bueno.</p>

<h2>5. Su contenido y las consultas</h2>
<p>Los datos de su proyecto (precios, disponibilidad, descripciones, fotos) son suyos y responde de que sean veraces y de que cumplan la normativa de publicidad inmobiliaria de su país. Las consultas de compradores que reciba son datos personales de terceros: debe tratarlas para atender su interés en el proyecto y conforme a la ley de protección de datos que le aplique.</p>

<h2>6. Uso aceptable</h2>
<p>No se puede usar la plataforma para publicar contenido ilícito, engañoso o que infrinja derechos de terceros, para enviar comunicaciones no solicitadas, ni para intentar acceder a datos o cuentas ajenos. Podemos suspender una cuenta que lo haga, avisando salvo urgencia.</p>

<h2>7. Disponibilidad y responsabilidad</h2>
<p>Trabajamos para que el servicio esté disponible de forma continua, con copias de seguridad diarias y vigilancia, pero no garantizamos que no haya interrupciones. No respondemos de las decisiones de compra que un tercero tome a partir de la información que usted publica, ni de daños indirectos o lucro cesante. Nuestra responsabilidad total frente a usted se limita a lo que nos haya pagado en los doce meses anteriores al hecho que la origine, salvo dolo o lo que la ley no permita limitar.</p>

<h2>8. Baja y conservación</h2>
<p>Puede eliminar su cuenta desde el panel. Al hacerlo se borran sus proyectos, viviendas y consultas, salvo lo que debamos conservar por obligación legal (facturación). Le recomendamos exportar antes sus datos, que también puede hacer desde el panel.</p>

<h2>9. Cambios en el servicio o en estas condiciones</h2>
<p>El servicio evoluciona; podemos añadir, cambiar o retirar funciones. Si un cambio de estas condiciones le afecta de forma sustancial, se lo comunicaremos con antelación y podrá cancelar sin coste si no lo acepta.</p>

<h2>10. Ley y jurisdicción</h2>
<p>Estas condiciones se rigen por la ley del país del domicilio de {{ $responsable }}. Para cualquier disputa, las partes se someten a los tribunales de ese domicilio, sin perjuicio de los derechos que como consumidor le reconozca la ley de su residencia.</p>

<p>Contacto: <a href="mailto:{{ $correo }}">{{ $correo }}</a>.</p>
