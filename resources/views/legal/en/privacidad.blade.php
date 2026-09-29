{{-- Privacy policy, in English. Company details come from the controller. --}}
<h2>1. Who processes your data</h2>
<p>The data controller is <strong>{{ $responsable }}</strong>, owner of the {{ $marca }} platform{{ $domicilio ? ', with registered address at '.$domicilio : '' }}{{ $registro ? ' ('.$registro.')' : '' }}. For any question about your data, write to <a href="mailto:{{ $correo }}">{{ $correo }}</a>.</p>
<p>This policy applies to those who create an account on {{ $marca }} (developers, agents and their teams), to those who visit a published project or write through it (interested buyers), and to those who browse the portal.</p>

<h2>2. What data we process and why</h2>
<ul>
    <li><strong>Developer or agent account:</strong> name, email, encrypted password, company, phone, country and city. To provide the service, manage your subscription, notify you about your account and answer your requests.</li>
    <li><strong>Billing:</strong> payments are processed by Stripe. We keep the customer identifier, the plan and the subscription status; never the full card number.</li>
    <li><strong>Buyer enquiries:</strong> name, email, phone and the message you write in a project's form or assistant. They are stored to deliver them to the project's developer, who is the one who answers you, and to send you an acknowledgement.</li>
    <li><strong>Assistant conversations:</strong> the messages you write to a project's assistant are processed by an artificial intelligence provider to generate the reply, and stored together with the enquiry if you decide to leave your contact details. The assistant makes no decisions with legal effects about you.</li>
    <li><strong>Viewer and portal usage:</strong> pages and units viewed, time in the viewer and device type, linked to a session identifier, not to your identity. They let the developer know what interests people about the project, and help us improve the service.</li>
    <li><strong>Technical logs:</strong> IP address, browser and date of each request, for a limited time, for the security of the service and to limit abuse.</li>
</ul>

<h2>3. On what basis</h2>
<p>We process account and billing data because they are necessary for the contract you accept when registering. Buyer enquiries are processed at your own request to be contacted. Technical logs and anti-abuse measures rely on our legitimate interest in keeping the service secure. Analytics cookies, where present, are only set with your consent (see the cookie policy).</p>

<h2>4. Who we share data with</h2>
<p>With the <strong>developer of the project</strong> you write to, who receives your enquiry and is responsible for how it is handled. And with the providers we need to run the service, who process data on our behalf and under contract: the hosting provider where the platform runs, Stripe for payments, the email delivery provider for notifications, and the artificial intelligence provider that generates the assistant's replies. We do not sell data or hand it to third parties for their own advertising.</p>
<p>Some of these providers may be outside your country. Where that is the case, we require adequate safeguards (standard contractual clauses or equivalent mechanisms).</p>

<h2>5. How long we keep it</h2>
<p>Account data, for as long as the account exists and, afterwards, for as long as tax and commercial law requires billing records to be kept. Buyer enquiries, while the project remains published and up to two years after the last activity, unless the developer or you ask us to delete them earlier. Technical logs, at most twelve months.</p>

<h2>6. Your rights</h2>
<p>You can access your data, correct it, ask us to delete it, object to a processing, restrict it, or take it with you in a reusable format. If you have an account, you can export and delete your data from the panel itself. Otherwise, write to <a href="mailto:{{ $correo }}">{{ $correo }}</a> and we will answer within the legal deadline.</p>
<p>If you are a resident of the Dominican Republic, you are protected by Law 172-13 on the protection of personal data and by the competent authority. If you are a resident of the European Union, you are protected by Regulation (EU) 2016/679 and may lodge a complaint with your country's supervisory authority.</p>

<h2>7. Security</h2>
<p>The platform is served encrypted (HTTPS), passwords are stored with a one-way algorithm, server access is restricted, and daily backups are taken and their restoration is verified. No system is infallible: if we detect a breach affecting your data, we will notify you as the law requires.</p>

<h2>8. Changes</h2>
<p>If we change this policy substantially, we will tell you in the panel or by email and ask you to accept the current version again. The version date appears at the top of this page.</p>
