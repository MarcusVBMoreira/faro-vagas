<x-layouts.app>
    @if (session('status'))
        <div class="bg-sinal px-6 py-3 text-center text-sm font-medium text-white" role="status">{{ session('status') }}</div>
    @endif

    {{-- Topo + destaque, em azul-noite --}}
    <div class="relative overflow-hidden bg-noite text-white">
        {{-- Feixes do farol, decorativos --}}
        <svg class="pointer-events-none absolute -right-40 top-1/2 h-[720px] w-[720px] -translate-y-1/2 opacity-[0.07] md:opacity-10" viewBox="0 0 64 64" aria-hidden="true">
            <path d="M32.43 21.53 A17 17 0 0 1 32.43 42.47" fill="none" stroke="#F5A524" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M41.85 14.15 A29 29 0 0 1 41.85 49.85" fill="none" stroke="#F5A524" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M51.27 6.77 A41 41 0 0 1 51.27 57.23" fill="none" stroke="#F5A524" stroke-width="2.2" stroke-linecap="round"/>
        </svg>

        <header class="relative mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
            <a href="{{ route('home') }}" class="flex items-center" aria-label="Faro, página inicial">
                <img src="{{ asset('images/faro-logo-dark.svg') }}" alt="Faro" class="h-9 w-auto">
            </a>
            <nav class="flex items-center gap-2 text-sm">
                <a href="#como-funciona" class="hidden rounded-md px-3 py-2 text-white/80 hover:text-white sm:inline-block">Como funciona</a>
                <a href="#status" class="hidden rounded-md px-3 py-2 text-white/80 hover:text-white sm:inline-block">Status</a>
                @auth
                    <a href="{{ route('profile.show') }}" class="btn btn-primary">Meu perfil</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-white/80 hover:text-white">Entrar</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Criar conta</a>
                @endauth
            </nav>
        </header>

        <section class="relative mx-auto grid max-w-6xl items-center gap-12 px-6 pb-24 pt-10 md:grid-cols-[1.1fr_1fr] md:pt-16">
            <div>
                <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-noite-line px-3 py-1 text-xs font-medium text-white/80">
                    <span class="h-1.5 w-1.5 rounded-full bg-farol"></span>
                    Beta · vagas de estágio e júnior em tecnologia
                </p>
                <h1 class="font-display text-4xl font-bold leading-[1.05] tracking-tight md:text-6xl">
                    Pare de procurar vaga.<br>
                    <span class="text-farol">Deixe o Faro encontrar.</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-white/75">
                    A IA que encontra as vagas certas para você. Envie seu currículo e veja as vagas mais compatíveis com o seu perfil, cada uma com uma nota e o motivo.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('profile.show') : route('register') }}" class="btn btn-primary h-12 px-6 text-base">Enviar meu currículo</a>
                    <a href="#como-funciona" class="btn btn-ghost-dark h-12 px-6 text-base">Ver como funciona</a>
                </div>
            </div>

            {{-- Exemplo ilustrativo de um card de vaga com match --}}
            <div class="relative" aria-label="Exemplo de vaga recomendada">
                <div class="absolute -left-4 top-6 hidden h-full w-full rounded-lg bg-noite-2 md:block"></div>
                <article class="relative rounded-lg bg-raised p-6 text-ink shadow-2xl shadow-black/30">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-ink-muted">Empresa Exemplo · Santo André, SP</p>
                            <h2 class="mt-1 font-display text-xl font-semibold">Estágio em Desenvolvimento PHP</h2>
                        </div>
                        <span class="shrink-0 rounded-full bg-sinal/10 px-3 py-1 text-sm font-semibold text-sinal">87% compatível</span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
                        <span class="rounded-sm bg-sunken px-2 py-1">Híbrido</span>
                        <span class="rounded-sm bg-sunken px-2 py-1">Estágio</span>
                        <span class="rounded-sm bg-farol-soft px-2 py-1">Nova hoje</span>
                    </div>
                    <div class="mt-5 space-y-3 border-t border-line pt-5 text-sm">
                        <p><span class="font-semibold text-success">Você tem:</span> PHP, MySQL, Git, HTML/CSS</p>
                        <p><span class="font-semibold text-accent-text">Falta:</span> Docker (diferencial, não obrigatório)</p>
                        <p class="flex gap-2 text-ink-muted">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-sinal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>
                            Aceita quem está a partir do 3º semestre, que é o seu caso.
                        </p>
                    </div>
                </article>
            </div>
        </section>
    </div>

    {{-- Como funciona --}}
    <section id="como-funciona" class="mx-auto max-w-6xl px-6 py-24">
        <h2 class="font-display text-3xl font-bold tracking-tight md:text-4xl">Como funciona</h2>
        <p class="mt-3 max-w-2xl text-ink-muted">Três passos. A IA faz o trabalho pesado, você decide para onde mandar o currículo.</p>

        <ol class="mt-12 grid gap-6 md:grid-cols-3">
            <li class="rounded-lg border border-line bg-raised p-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-noite text-farol">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M12 18v-6"/><path d="m9 15 3-3 3 3"/></svg>
                </span>
                <h3 class="mt-5 font-display text-xl font-semibold">1. Envie seu currículo</h3>
                <p class="mt-2 text-ink-muted">A IA lê o PDF e monta seu perfil: habilidades, nível, área e preferências. Você revisa e ajusta.</p>
            </li>
            <li class="rounded-lg border border-line bg-raised p-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-noite text-farol">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="12" r="2"/><path d="M10.5 7.5a6.4 6.4 0 0 1 0 9"/><path d="M14.5 4a11.3 11.3 0 0 1 0 16"/></svg>
                </span>
                <h3 class="mt-5 font-display text-xl font-semibold">2. O Faro procura</h3>
                <p class="mt-2 text-ink-muted">Todo dia o sistema coleta vagas novas de fontes públicas e organiza cada uma: nível, modalidade, cidade e tecnologias.</p>
            </li>
            <li class="rounded-lg border border-line bg-raised p-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-noite text-farol">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <h3 class="mt-5 font-display text-xl font-semibold">3. Veja o porquê</h3>
                <p class="mt-2 text-ink-muted">Cada vaga recomendada vem com uma nota de compatibilidade, seus pontos fortes e o que falta aprender.</p>
            </li>
        </ol>
    </section>

    {{-- Status do projeto --}}
    <section id="status" class="border-t border-line bg-sunken">
        <div class="mx-auto grid max-w-6xl gap-10 px-6 py-20 md:grid-cols-2">
            <div>
                <h2 class="font-display text-3xl font-bold tracking-tight">Construído em público</h2>
                <p class="mt-3 text-ink-muted">
                    O Faro é um projeto de portfólio em desenvolvimento. Cada etapa vai para o GitHub e para o ar ao mesmo tempo, com testes automáticos e deploy contínuo.
                </p>
                <a href="{{ config('faro.repo_url') }}" class="btn btn-secondary mt-6" target="_blank" rel="noopener">Ver código e roadmap</a>
            </div>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-3 rounded-md bg-raised p-4"><span class="h-2.5 w-2.5 rounded-full bg-success"></span> Identidade visual, banco de dados e deploy automático</li>
                <li class="flex items-center gap-3 rounded-md bg-raised p-4"><span class="h-2.5 w-2.5 rounded-full bg-success"></span> IA própria no servidor do projeto (sem enviar currículos a terceiros)</li>
                <li class="flex items-center gap-3 rounded-md bg-raised p-4"><span class="h-2.5 w-2.5 rounded-full bg-success"></span> Semana 1: contas, upload de currículo e perfil com IA</li>
                <li class="flex items-center gap-3 rounded-md bg-raised p-4"><span class="h-2.5 w-2.5 rounded-full bg-farol"></span> Semana 2: coleta automática de vagas</li>
                <li class="flex items-center gap-3 rounded-md bg-raised p-4"><span class="h-2.5 w-2.5 rounded-full bg-line"></span> Semana 3: match com IA</li>
                <li class="flex items-center gap-3 rounded-md bg-raised p-4"><span class="h-2.5 w-2.5 rounded-full bg-line"></span> Semana 4: Kanban, e-mail diário e acabamento</li>
            </ul>
        </div>
    </section>

    <footer class="bg-noite text-white/70">
        <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-4 px-6 py-10 text-sm sm:flex-row sm:items-center">
            <img src="{{ asset('images/faro-logo-dark.svg') }}" alt="Faro" class="h-7 w-auto">
            <p>
                Projeto de portfólio de Marcus Moreira · {{ now()->year }} ·
                <a href="{{ route('privacy') }}" class="underline hover:text-white">Privacidade</a> ·
                <a href="{{ config('faro.repo_url') }}" class="underline hover:text-white" target="_blank" rel="noopener">GitHub</a>
            </p>
        </div>
    </footer>
</x-layouts.app>
