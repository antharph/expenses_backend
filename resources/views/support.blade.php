<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Support — {{ config('app.name') }}</title>
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
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: var(--bg);
                color: var(--text);
                line-height: 1.5;
            }

            main {
                width: 100%;
                max-width: 28rem;
                padding: 2rem;
                background: var(--card);
                border-radius: 0.5rem;
                box-shadow: inset 0 0 0 1px var(--border);
            }

            h1 {
                margin: 0 0 0.5rem;
                font-size: 1.25rem;
                font-weight: 500;
            }

            p {
                margin: 0 0 1.5rem;
                font-size: 0.875rem;
                color: var(--muted);
            }

            dl {
                margin: 0;
                font-size: 0.875rem;
            }

            .contact-item + .contact-item {
                margin-top: 1rem;
            }

            dt {
                margin-bottom: 0.25rem;
                font-weight: 500;
            }

            dd {
                margin: 0;
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
            <h1>Support</h1>
            <p>
                If you need help with the app, you can reach me using the contact
                details below.
            </p>

            <dl>
                <div class="contact-item">
                    <dt>Email</dt>
                    <dd>
                        <a href="mailto:antharph@gmail.com">antharph@gmail.com</a>
                    </dd>
                </div>
                <div class="contact-item">
                    <dt>Mobile</dt>
                    <dd>
                        <a href="tel:+639989566051">+639989566051</a>
                    </dd>
                </div>
            </dl>

            <a class="back-link" href="{{ route('home') }}">Back to home</a>
        </main>
    </body>
</html>
