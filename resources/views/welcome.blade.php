<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inte-School | Sovereign AI Communication for Scottish Schools</title>
    <meta name="description" content="The only sovereign AI communication platform for Scottish schools. UK-hosted, GDPR-immune, PEF eligible. Built in Livingston by Inte.Team.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6b21a8;
            --secondary: #f97316;
            --warm-bg: #fffbf7;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--warm-bg); }
        .gradient-text {
            background: linear-gradient(90deg, #6b21a8, #9333ea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-pattern {
            background-color: #fffbf7;
            background-image: radial-gradient(#f9731633 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="text-slate-800">

    <nav class="p-6 flex justify-between items-center bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-orange-100">
        <div class="text-2xl font-bold flex items-center gap-2">
            <div class="w-8 h-8 bg-purple-700 rounded-lg flex items-center justify-center text-white text-sm">inte</div>
            <span class="tracking-tight text-purple-900">School</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="/login" class="text-purple-700 hover:text-purple-900 font-medium text-sm transition-all">
                Sign In
            </a>
            <a href="#contact" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded-full font-medium transition-all text-sm">
                Book a Demo
            </a>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero-pattern px-6 py-16 md:py-32 text-center max-w-5xl mx-auto">
        <span class="bg-orange-100 text-orange-700 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest">Built for Scotland 2026</span>
        <h1 class="text-4xl md:text-6xl font-extrabold mt-6 leading-tight text-slate-900">
            The Only Sovereign AI Platform <span class="gradient-text">for Scottish Schools</span>
        </h1>
        <p class="mt-6 text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Eliminate US-cloud data leakage and dramatically reduce messaging costs with locally-hosted, GDPR-immune infrastructure. Built in Livingston, powered by open-source engineering.
        </p>
        <div class="mt-10 flex flex-col md:flex-row gap-4 justify-center">
            <a href="#contact" class="bg-purple-700 text-white px-8 py-4 rounded-xl font-bold shadow-lg shadow-purple-200 hover:-translate-y-1 transition-all inline-block">
                Start Pilot (PEF Eligible)
            </a>
            <a href="#how-it-works" class="bg-white border-2 border-slate-200 text-slate-700 px-8 py-4 rounded-xl font-bold hover:bg-slate-50 transition-all inline-block">
                See How It Works
            </a>
        </div>
    </header>

    <!-- Three Pillars: Outcomes, not features -->
    <section class="px-6 py-20 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-3 gap-12">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-700 text-xl">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="text-xl font-bold">Data Sovereignty</h3>
                    <p class="text-slate-500 leading-relaxed">No Firebase. No US-cloud. All pupil data stays on UK-dedicated infrastructure in isolated school stacks. Your data never leaves the country.</p>
                </div>
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-xl">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <h3 class="text-xl font-bold">5 Hours of Admin Saved Daily</h3>
                    <p class="text-slate-500 leading-relaxed">Every message becomes a threaded conversation with read receipts and automated follow-ups. No more chasing replies by phone or digging through email.</p>
                </div>
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <h3 class="text-xl font-bold">The School Oracle</h3>
                    <p class="text-slate-500 leading-relaxed">Stop the 9 AM phone rush. Our self-hosted AI reads your school handbook and answers parent questions instantly&mdash;uniform policies, term dates, lunch menus&mdash;without a single staff member picking up the phone.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Private AI / Hardware -->
    <section class="px-6 py-16">
        <div class="max-w-4xl mx-auto bg-gradient-to-br from-slate-900 to-purple-950 rounded-3xl p-8 md:p-12 text-white overflow-hidden relative">
            <div class="relative z-10">
                <span class="bg-purple-500/20 text-purple-300 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest">Private AI</span>
                <h2 class="text-3xl font-bold mt-4 mb-3">Local Hardware. Zero Data Leakage.</h2>
                <p class="text-slate-300 leading-relaxed max-w-2xl mb-8">
                    Unlike platforms that route your school's data through US cloud providers, Inte-School runs on dedicated UK infrastructure. The School Oracle AI processes queries entirely on our private servers&mdash;no data is ever sent to OpenAI, Google, or any third party.
                </p>
                <div class="grid sm:grid-cols-3 gap-6">
                    <div class="bg-white/5 rounded-xl p-5 border border-white/10">
                        <div class="text-orange-400 text-lg mb-2"><i class="fa-solid fa-microchip"></i></div>
                        <p class="font-bold text-sm">Self-Hosted Models</p>
                        <p class="text-slate-400 text-xs mt-1">AI runs on our UK rack, never cloud APIs</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-5 border border-white/10">
                        <div class="text-orange-400 text-lg mb-2"><i class="fa-solid fa-lock"></i></div>
                        <p class="font-bold text-sm">100% Offline-Capable</p>
                        <p class="text-slate-400 text-xs mt-1">No external dependencies for core features</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-5 border border-white/10">
                        <div class="text-orange-400 text-lg mb-2"><i class="fa-solid fa-child"></i></div>
                        <p class="font-bold text-sm">UK Children's Code</p>
                        <p class="text-slate-400 text-xs mt-1">Privacy-by-default for all pupil data</p>
                    </div>
                </div>
            </div>
            <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-purple-600/20 blur-3xl rounded-full"></div>
        </div>
    </section>

    <!-- Guaranteed Delivery Cascade -->
    <section id="how-it-works" class="px-6 py-16">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-900">Guaranteed Delivery Cascade</h2>
                <p class="text-slate-500 mt-3">Every message reaches every parent. No exceptions.</p>
            </div>
            <div class="space-y-6">
                <div class="flex gap-5 items-start bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="bg-green-500 text-white w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold">1</div>
                    <div>
                        <p class="font-bold text-slate-900">Real-time WebSocket (Laravel Reverb)</p>
                        <p class="text-slate-500 text-sm mt-1">Instant sync without the spinning wheel. If the parent has the app open, they see it immediately.</p>
                    </div>
                </div>
                <div class="flex gap-5 items-start bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="bg-blue-500 text-white w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold">2</div>
                    <div>
                        <p class="font-bold text-slate-900">Native Web Push (VAPID)</p>
                        <p class="text-slate-500 text-sm mt-1">OS-level notifications sent to verified devices. No Google or Firebase trackers involved.</p>
                    </div>
                </div>
                <div class="flex gap-5 items-start bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="bg-orange-500 text-white w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold">3</div>
                    <div>
                        <p class="font-bold text-slate-900">Emergency SMS Fallback</p>
                        <p class="text-slate-500 text-sm mt-1">Unread after 15 minutes? Automatically promoted to SMS for critical alerts. No parent left behind.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SMS Transparency -->
    <section class="px-6 py-20 bg-white border-y border-slate-100">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2 space-y-6">
                    <h2 class="text-3xl font-bold text-slate-900 leading-tight">
                        Stop Paying the <span class="text-orange-500">'SMS Tax'</span>
                    </h2>
                    <p class="text-slate-600 leading-relaxed">
                        Most school apps charge a markup on every text you send. We don't. Inte-School integrates with <strong>GOV.UK Notify</strong>&mdash;the same service used by the NHS and HMRC.
                    </p>
                    <p class="text-slate-600 leading-relaxed">
                        That means your first <strong>5,000 emergency texts every year cost you absolutely nothing</strong>. We charge zero markup on messaging. We grow when you save time, not when you send texts.
                    </p>
                </div>
                <div class="md:w-1/2">
                    <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center pb-4 border-b border-slate-200">
                                <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">Annual SMS cost</span>
                                <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">Price</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-700">First 5,000 texts</span>
                                <span class="text-2xl font-extrabold text-green-600">&pound;0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-700">Additional texts</span>
                                <span class="text-slate-700 font-bold">2.4p + VAT each</span>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-slate-200">
                                <span class="text-slate-700">Our markup</span>
                                <span class="text-2xl font-extrabold text-green-600">&pound;0</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 mt-6">Via GOV.UK Notify. Same infrastructure as NHS and HMRC.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Engineering / Livingston -->
    <section class="px-6 py-20 bg-slate-50">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2 space-y-6">
                    <h2 class="text-3xl font-bold text-slate-900 leading-tight">
                        Engineering Excellence, <br>
                        <span class="text-purple-700">Made in Livingston.</span>
                    </h2>
                    <p class="text-slate-600 leading-relaxed">
                        We aren't a faceless corporation. Inte-School is built by <strong>Inte.Team</strong>&mdash;a local business with 300+ five-star Google reviews. When you need support, you're not calling a global helpdesk. You're calling the lead architect.
                    </p>
                    <div class="flex items-center gap-4">
                        <div class="flex text-orange-400">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <span class="font-bold text-slate-700">300+ Five-Star Google Reviews</span>
                    </div>
                </div>

                <div class="md:w-1/2 bg-white p-8 rounded-3xl shadow-xl shadow-slate-200 border border-slate-100">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-slate-900 rounded-full flex items-center justify-center text-white">
                            <i class="fa-solid fa-code"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">Piotr Ficner</h4>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Founder &amp; Lead Architect</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 italic leading-relaxed">
                        "Schools deserve the same security as enterprise banks. That's why I contributed the <strong>Two-Factor Authentication (2FA)</strong> features to NGINX Proxy Manager&mdash;a tool used by millions to secure web traffic. The same engineering rigour powers every line of Inte-School."
                    </p>
                    <div class="mt-6 pt-6 border-t border-slate-50 flex justify-between items-center">
                        <span class="text-xs font-mono text-purple-600">nginx-proxy-manager / contributor</span>
                        <a href="https://inte.team" class="text-purple-700 font-bold text-sm hover:underline">Visit Inte.Team &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technical Stack (for the IT lead scrolling down) -->
    <section class="px-6 py-16 bg-white">
        <div class="max-w-4xl mx-auto text-center">
            <span class="bg-slate-100 text-slate-500 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest">For IT Leads</span>
            <h2 class="text-2xl font-bold text-slate-900 mt-4 mb-8">Under the Bonnet</h2>
            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-6 text-left">
                <div class="p-5 rounded-xl border border-slate-100 bg-slate-50">
                    <p class="font-bold text-sm text-slate-900">Laravel Reverb</p>
                    <p class="text-slate-500 text-xs mt-1">WebSocket server. Real-time sync without polling or third-party services.</p>
                </div>
                <div class="p-5 rounded-xl border border-slate-100 bg-slate-50">
                    <p class="font-bold text-sm text-slate-900">VAPID Web Push</p>
                    <p class="text-slate-500 text-xs mt-1">Native notifications that bypass Google/Firebase entirely.</p>
                </div>
                <div class="p-5 rounded-xl border border-slate-100 bg-slate-50">
                    <p class="font-bold text-sm text-slate-900">PGVector RAG</p>
                    <p class="text-slate-500 text-xs mt-1">AI document search with local embeddings. No external API calls.</p>
                </div>
                <div class="p-5 rounded-xl border border-slate-100 bg-slate-50">
                    <p class="font-bold text-sm text-slate-900">Multi-Tenant Isolation</p>
                    <p class="text-slate-500 text-xs mt-1">Each school in its own data silo. Zero cross-tenant leakage.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="px-6 py-20 bg-slate-50 border-t border-slate-100">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold text-slate-900 mb-4">Secure Your School's Data</h2>
            <p class="text-slate-600 mb-10">Interested in a pilot? Book a demo or ask us anything. PEF-eligible pricing available.</p>
            <div id="form-436a7bdf-adb7-4c6f-9977-9e0f0537b1cb"></div>
        </div>
    </section>
    <script src="https://crm.bookrepaironline.co.uk/js/form-embed.js"></script>
    <script>
        FormEmbed.render('436a7bdf-adb7-4c6f-9977-9e0f0537b1cb', {
            container: '#form-436a7bdf-adb7-4c6f-9977-9e0f0537b1cb',
            apiBase: 'https://crm.bookrepaironline.co.uk',
            theme: 'light'
        });
    </script>

    <!-- Trust Strip -->
    <section class="px-6 py-12 bg-white border-t border-slate-100">
        <div class="max-w-3xl mx-auto text-center">
            <p class="text-4xl font-extrabold text-slate-900">784 <span class="text-purple-700">and growing</span></p>
            <p class="text-slate-600 mt-3 leading-relaxed max-w-xl mx-auto">
                automated quality checks run before any update reaches your school. Every button, every message, every permission boundary&mdash;tested. We engineered the mistakes out.
            </p>
            <p class="text-xs text-slate-400 mt-4 uppercase tracking-widest">Built to be un-breakable by design</p>
        </div>
    </section>

    <footer class="bg-white px-6 py-12">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-2 mb-2">
                    <div class="w-6 h-6 bg-purple-700 rounded flex items-center justify-center text-white text-[10px]">inte</div>
                    <span class="font-bold tracking-tight text-purple-900">School</span>
                </div>
                <p class="text-xs text-slate-500">
                    A product of <a href="https://inte.team" class="underline hover:text-purple-700">Inte.Team</a>.
                    Engineering excellence for Scottish Education.
                </p>
            </div>
            <div class="flex gap-8">
                <div class="text-center">
                    <p class="text-2xl font-bold text-slate-900">300+</p>
                    <p class="text-[10px] uppercase tracking-widest text-slate-400">Happy Clients</p>
                </div>
                <div class="text-center border-l border-slate-100 pl-8">
                    <p class="text-2xl font-bold text-slate-900">UK</p>
                    <p class="text-[10px] uppercase tracking-widest text-slate-400">Data Residency</p>
                </div>
                <div class="text-center border-l border-slate-100 pl-8">
                    <p class="text-2xl font-bold text-slate-900">0</p>
                    <p class="text-[10px] uppercase tracking-widest text-slate-400">SMS Markup</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-12 text-[10px] text-slate-400 uppercase tracking-widest">
            &copy; 2026 Inte-School | Developed by Inte.Team | UK/EU GDPR Compliant
        </div>
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100', 'translate-y-0');
                    entry.target.classList.remove('opacity-0', 'translate-y-10');
                }
            });
        });

        document.querySelectorAll('section').forEach(section => {
            section.classList.add('transition-all', 'duration-700', 'opacity-0', 'translate-y-10');
            observer.observe(section);
        });
    </script>
</body>
</html>
