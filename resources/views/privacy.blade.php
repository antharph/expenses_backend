<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy — MaiExpenses</title>
        <style>
            :root {
                color-scheme: light dark;
                --bg: #fdfdfc;
                --card: #ffffff;
                --text: #1b1b18;
                --muted: #706f6c;
                --link: #f53003;
                --border: rgba(26, 26, 0, 0.16);
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #0a0a0a;
                    --card: #161615;
                    --text: #ededec;
                    --muted: #a1a09a;
                    --link: #ff4433;
                    --border: #fffaed2d;
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                padding: 1.5rem;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: var(--bg);
                color: var(--text);
                line-height: 1.6;
            }

            main {
                width: 100%;
                max-width: 42rem;
                margin: 0 auto;
                padding: 2rem;
                background: var(--card);
                border-radius: 0.5rem;
                box-shadow: inset 0 0 0 1px var(--border);
            }

            h1 {
                margin: 0 0 0.25rem;
                font-size: 1.5rem;
                font-weight: 500;
            }

            .updated {
                margin: 0 0 1.5rem;
                font-size: 0.8125rem;
                color: var(--muted);
            }

            h2 {
                margin: 2rem 0 0.75rem;
                font-size: 1rem;
                font-weight: 500;
            }

            p,
            ul {
                margin: 0 0 1rem;
                font-size: 0.875rem;
                color: var(--muted);
            }

            ul {
                padding-left: 1.25rem;
            }

            li + li {
                margin-top: 0.35rem;
            }

            a {
                color: var(--link);
            }

            .back-link {
                display: inline-block;
                margin-top: 2rem;
                font-size: 0.875rem;
                color: var(--muted);
            }
        </style>
    </head>
    <body>
        <main>
            <h1>Privacy Policy</h1>
            <p class="updated">Last updated: {{ now()->format('F j, Y') }}</p>

            <p>
                This Privacy Policy describes how <strong>MaiExpenses</strong>
                (“we”, “us”, or “our”) collects, uses, and protects information
                when you use the MaiExpenses mobile application and related
                services (collectively, the “Service”).
            </p>
            <p>
                By using the Service, you agree to the collection and use of
                information in accordance with this policy. If you do not agree,
                please do not use the Service.
            </p>

            <h2>1. Information we collect</h2>
            <p>We collect information you provide and data generated through your use of the Service:</p>
            <ul>
                <li>
                    <strong>Account information</strong> — such as your name,
                    email address, and timezone when you register or sign in
                    (including via Google or other supported sign-in providers).
                </li>
                <li>
                    <strong>Expense records</strong> — such as item names,
                    quantities, prices, categories, dates, budget settings, and
                    other details you enter or that are stored on your behalf.
                </li>
                <li>
                    <strong>Receipt images</strong> — photos you capture or
                    upload from your device to attach to an expense.
                </li>
                <li>
                    <strong>Authentication data</strong> — tokens used to keep
                    you signed in securely between sessions.
                </li>
                <li>
                    <strong>Technical data</strong> — basic device and app usage
                    information needed to operate the Service (for example,
                    request logs and error reports).
                </li>
            </ul>

            <h2>2. How we use your information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Provide, maintain, and improve the Service;</li>
                <li>Create and manage your account;</li>
                <li>Store, sync, and display your expenses and budgets;</li>
                <li>Process receipt uploads you choose to attach to expenses;</li>
                <li>Authenticate you and protect against unauthorized access;</li>
                <li>Respond to support requests; and</li>
                <li>Comply with legal obligations where applicable.</li>
            </ul>
            <p>
                We do not sell your personal information to third parties.
            </p>

            <h2>3. How we store and protect data</h2>
            <p>
                Your data is stored on servers operated by us or our hosting
                providers. We use reasonable technical and organizational
                measures to protect your information, including encrypted
                connections (HTTPS/TLS) for data in transit.
            </p>
            <p>
                No method of transmission or storage is completely secure. While
                we work to protect your information, we cannot guarantee absolute
                security.
            </p>

            <h2>4. Third-party services</h2>
            <p>
                The Service may rely on third-party providers to operate,
                including:
            </p>
            <ul>
                <li>
                    <strong>Google Sign-In / Firebase Authentication</strong> —
                    when you choose to sign in with Google, Google processes
                    information according to
                    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google’s Privacy Policy</a>.
                </li>
                <li>
                    <strong>Cloud infrastructure</strong> — to host the
                    application and store your account and expense data.
                </li>
            </ul>
            <p>
                These providers only receive information necessary to perform
                their services on our behalf or as directed by your sign-in
                choices.
            </p>

            <h2>5. Data retention</h2>
            <p>
                We retain your account and expense data for as long as your
                account is active or as needed to provide the Service. If you
                delete your account or request deletion, we will delete or
                anonymize your personal data within a reasonable period, except
                where we are required to retain it by law.
            </p>

            <h2>6. Your choices and rights</h2>
            <p>Depending on where you live, you may have the right to:</p>
            <ul>
                <li>Access the personal information we hold about you;</li>
                <li>Correct inaccurate information in your account profile;</li>
                <li>Delete your account and associated data;</li>
                <li>Withdraw consent where processing is based on consent; and</li>
                <li>Object to or restrict certain processing.</li>
            </ul>
            <p>
                You can update profile details in the app where available. For
                other requests, contact us using the details below.
            </p>

            <h2>7. Children’s privacy</h2>
            <p>
                The Service is not directed to children under 13 (or the
                applicable age of digital consent in your country). We do not
                knowingly collect personal information from children. If you
                believe a child has provided us with personal information,
                please contact us and we will take steps to delete it.
            </p>

            <h2>8. International users</h2>
            <p>
                Your information may be processed and stored in countries other
                than your own. By using the Service, you consent to the transfer
                of your information to those locations, subject to this policy.
            </p>

            <h2>9. Changes to this policy</h2>
            <p>
                We may update this Privacy Policy from time to time. We will
                post the revised policy on this page and update the “Last
                updated” date. Continued use of the Service after changes take
                effect constitutes acceptance of the updated policy.
            </p>

            <h2>10. Contact us</h2>
            <p>
                If you have questions about this Privacy Policy or how we handle
                your data, contact us at:
            </p>
            <ul>
                <li>
                    Email:
                    <a href="mailto:antharph@gmail.com">antharph@gmail.com</a>
                </li>
                <li>
                    Mobile:
                    <a href="tel:+639989566051">+639989566051</a>
                </li>
            </ul>

            <a class="back-link" href="{{ route('home') }}">Back to home</a>
        </main>
    </body>
</html>
