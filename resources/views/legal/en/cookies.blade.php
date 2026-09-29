{{-- Cookie policy, in English. --}}
<h2>1. What they are</h2>
<p>A cookie is a small file the browser stores when you visit a website, which lets it remember things between one page and the next: that you are logged in, which language you prefer. {{ $marca }} uses few, and analytics ones only if you accept them.</p>

<h2>2. Necessary cookies</h2>
<p>They are always set, because the service does not work without them. They do not require consent.</p>
<ul>
    <li><strong>Session</strong> (<code>real3d_session</code> or similar): keeps you logged in while you browse. It expires when you close the browser or after two hours of inactivity.</li>
    <li><strong>Form protection</strong> (<code>XSRF-TOKEN</code>): prevents another website from submitting forms on your behalf.</li>
    <li><strong>Language and currency</strong>: remember your preference when you change it.</li>
    <li><strong>Remember me</strong>: only if you tick that box when logging in, so you do not have to do it every time.</li>
</ul>
<p>The viewer also keeps, in the browser's local storage, an anonymous session identifier and, if you use the assistant, the ongoing conversation, so it is not lost when you change page. They do not leave your browser except for the use described in the privacy policy.</p>

<h2>3. Analytics cookies</h2>
<p>Some projects use Google Analytics so the developer knows how many people visit its viewer and what they look at. Those cookies (<code>_ga</code>, <code>_ga_*</code>) are only set if you accept them in the notice shown when the viewer opens. If you do not accept, the viewer works the same and they are not set.</p>

<h2>4. Changing your mind</h2>
<p>You can withdraw consent to analytics cookies from the notice itself, which appears again if you clear the site's data in your browser, or by deleting cookies from the browser settings. You can also set the browser to reject all cookies, although then you will not be able to log in.</p>

<h2>5. Contact</h2>
<p>For any question about cookies or your data: <a href="mailto:{{ $correo }}">{{ $correo }}</a>. The controller is {{ $responsable }}.</p>
