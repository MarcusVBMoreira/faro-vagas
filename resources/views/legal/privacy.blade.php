<x-layouts.app :title="__('Política de privacidade')">
    <header class="sticky top-0 z-30 border-b border-line/60 bg-surface/90 backdrop-blur">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" aria-label="{{ __('Faro, página inicial') }}">
                <img src="{{ asset('images/faro-logo.svg') }}" alt="Faro" class="h-8 w-auto">
            </a>
            <x-locale-switcher />
        </div>
    </header>

    <div class="mx-auto max-w-3xl px-6 py-12">

        <h1 class="font-display text-4xl font-bold tracking-tight">{{ __('Política de privacidade') }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ __('Atualizada em 4 de novembro de 2026') }}</p>

        <div class="mt-8 space-y-6 leading-relaxed text-ink">
            <p>{{ __('O Faro é um projeto de portfólio que usa inteligência artificial para encontrar vagas compatíveis com o seu currículo, em todas as áreas profissionais. Esta página explica, de forma direta, o que fazemos com os seus dados.') }}</p>

            <section>
                <h2 class="font-display text-xl font-semibold">{{ __('Quais dados guardamos') }}</h2>
                <ul class="mt-2 list-disc space-y-1 pl-6">
                    <li>{{ __('Nome, e-mail e senha (a senha é guardada criptografada, ninguém consegue lê-la).') }}</li>
                    <li>{{ __('Foto de perfil, se você quiser enviar uma.') }}</li>
                    <li>{{ __('O currículo em PDF que você enviar e o texto extraído dele.') }}</li>
                    <li>{{ __('O perfil profissional: cargo desejado, área, experiências, formação, idiomas, habilidades, cidade, contato e preferências.') }}</li>
                    <li>{{ __('As vagas que você salvar e o andamento das suas candidaturas.') }}</li>
                    <li>{{ __('Códigos de verificação enviados por e-mail, guardados apenas como hash e por 15 minutos.') }}</li>
                </ul>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">{{ __('Dado sensível: PcD') }}</h2>
                <p class="mt-2">{{ __('Informar se você é uma pessoa com deficiência é opcional e só acontece com a sua escolha explícita. A informação serve apenas para mostrar vagas afirmativas e nunca entra na nota de compatibilidade. Você pode mudar para "Prefiro não informar" a qualquer momento.') }}</p>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">{{ __('Para que usamos') }}</h2>
                <p class="mt-2">{{ __('Só para montar o seu perfil e recomendar vagas. Não vendemos, não compartilhamos e não usamos seus dados para publicidade.') }}</p>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">{{ __('Onde a IA roda') }}</h2>
                <p class="mt-2">{{ __('A leitura do currículo é feita por um modelo de IA aberto que roda num servidor próprio do projeto. O seu currículo não é enviado para serviços de IA de terceiros, como ChatGPT ou Gemini.') }}</p>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">{{ __('Seus direitos (LGPD)') }}</h2>
                <p class="mt-2">{{ __('Você pode ver e corrigir o seu perfil a qualquer momento na página "Meu perfil". Também pode excluir a sua conta por lá: isso apaga definitivamente seus dados, foto, currículos e candidaturas.') }}</p>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">{{ __('Contato') }}</h2>
                <p class="mt-2">{{ __('Dúvidas sobre os seus dados:') }} <a href="mailto:contato@marcusmoreira.com.br" class="font-medium text-accent-text underline">contato@marcusmoreira.com.br</a>.</p>
            </section>
        </div>
    </div>
</x-layouts.app>
