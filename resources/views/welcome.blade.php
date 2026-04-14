<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inte-School | Scottish Education Communication</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6b21a8; /* Heather Purple */
            --secondary: #f97316; /* Thistle Orange */
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

    <header class="hero-pattern px-6 py-16 md:py-32 text-center max-w-5xl mx-auto">
        <span class="bg-orange-100 text-orange-700 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest">Built for Scotland 2026</span>
        <h1 class="text-4xl md:text-6xl font-extrabold mt-6 leading-tight text-slate-900">
            Close the Attainment Gap with <span class="gradient-text">Intelligent Engagement</span>
        </h1>
        <p class="mt-6 text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
            The only two-way communication platform designed to bypass cloud-middlemen, ensuring 100% GDPR sovereignty and higher parental engagement.
        </p>
        <div class="mt-10 flex flex-col md:flex-row gap-4 justify-center">
            <a href="#contact" class="bg-purple-700 text-white px-8 py-4 rounded-xl font-bold shadow-lg shadow-purple-200 hover:-translate-y-1 transition-all inline-block">
                Start Pilot (PEF Eligible)
            </a>
            <a href="#contact" class="bg-white border-2 border-slate-200 text-slate-700 px-8 py-4 rounded-xl font-bold hover:bg-slate-50 transition-all inline-block">
                View Compliance Pack
            </a>
        </div>
    </header>

    <section class="px-6 py-20 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-3 gap-12">
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-700 text-xl">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="text-xl font-bold">Data Sovereignty</h3>
                    <p class="text-slate-500 leading-relaxed">No Firebase. No US-cloud data leakage. All pupil data stays on UK-dedicated infrastructure in isolated school stacks.</p>
                </div>
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-xl">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h3 class="text-xl font-bold">Two-Way by Default</h3>
                    <p class="text-slate-500 leading-relaxed">Unique Transaction IDs turn every message into a threaded reply. No more unstructured email piles or lost consent forms.</p>
                </div>
                <div class="space-y-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <h3 class="text-xl font-bold">Self-Hosted RAG AI</h3>
                    <p class="text-slate-500 leading-relaxed">Parents get instant answers from your school handbook via a fully local AI that never leaves your server.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-6 py-12">
        <div class="max-w-4xl mx-auto bg-slate-900 rounded-3xl p-8 md:p-12 text-white overflow-hidden relative">
            <div class="relative z-10">
                <h2 class="text-3xl font-bold mb-6">Guaranteed Delivery Cascade</h2>
                <div class="space-y-6">
                    <div class="flex gap-4 items-start">
                        <div class="bg-green-500 w-6 h-6 rounded-full flex-shrink-0 mt-1 flex items-center justify-center text-[10px]">1</div>
                        <div>
                            <p class="font-bold">Real-time WebSocket (Reverb)</p>
                            <p class="text-slate-400 text-sm">Instant delivery if the parent has the app open.</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="bg-blue-500 w-6 h-6 rounded-full flex-shrink-0 mt-1 flex items-center justify-center text-[10px]">2</div>
                        <div>
                            <p class="font-bold">Native Web Push (VAPID)</p>
                            <p class="text-slate-400 text-sm">OS-level notification sent to verified registered devices.</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="bg-orange-500 w-6 h-6 rounded-full flex-shrink-0 mt-1 flex items-center justify-center text-[10px]">3</div>
                        <div>
                            <p class="font-bold">Emergency SMS Fallback</p>
                            <p class="text-slate-400 text-sm">Automated promotion after 15 minutes of non-read for critical alerts.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-purple-600/20 blur-3xl rounded-full"></div>
        </div>
    </section>

    <section class="px-6 py-20 bg-slate-50 border-y border-slate-100">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2 space-y-6">
                    <h2 class="text-3xl font-bold text-slate-900 leading-tight">
                        World-Class Engineering. <br>
                        <span class="text-purple-700">Local Accountability.</span>
                    </h2>
                    <p class="text-slate-600 leading-relaxed">
                        Inte-School isn't just another software package. It is built by <strong>Inte.Team</strong>, a local business with a reputation for excellence. We don't just use technology; we contribute to the global open-source ecosystem that powers the modern web.
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
                            <p class="text-xs text-slate-500 uppercase tracking-wider">Founder & Lead Architect</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 italic leading-relaxed">
                        "We believe schools deserve the same security as enterprise banks. That's why our founder contributed directly to the <strong>Two-Factor Authentication (2FA)</strong> features in NGINX Proxy Manager&mdash;a tool used by millions to secure web traffic."
                    </p>
                    <div class="mt-6 pt-6 border-t border-slate-50 flex justify-between items-center">
                        <span class="text-xs font-mono text-purple-600">nginx-proxy-manager / contributor</span>
                        <a href="https://inte.team" class="text-purple-700 font-bold text-sm hover:underline">Visit Inte.Team &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="px-6 py-20 bg-white">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold text-slate-900 mb-4">Get in Touch</h2>
            <p class="text-slate-600 mb-10">Interested in a pilot? Book a demo or ask us anything.</p>
            <div id="form-ecdd1529-d1fa-48d5-82b5-1362536b46dc"></div>
        </div>
    </section>
    <script src="https://crm.bookrepaironline.co.uk/js/form-embed.js"></script>
    <script>
        FormEmbed.render('ecdd1529-d1fa-48d5-82b5-1362536b46dc', {
            container: '#form-ecdd1529-d1fa-48d5-82b5-1362536b46dc',
            apiBase: 'https://crm.bookrepaironline.co.uk',
            theme: 'light'
        });
    </script>

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
            </div>
        </div>
        <div class="text-center mt-12 text-[10px] text-slate-400 uppercase tracking-widest">
            &copy; 2026 Inte-School | Developed by Inte.Team | UK/EU GDPR Compliant
        </div>
    </footer>

    <script>
        // Simple scroll reveal effect
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
