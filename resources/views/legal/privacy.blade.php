<x-layouts.app title="Política de privacidade">
    <div class="mx-auto max-w-3xl px-6 py-12">
        <a href="{{ route('home') }}" aria-label="Faro, página inicial">
            <img src="{{ asset('images/faro-logo.svg') }}" alt="Faro" class="h-8 w-auto">
        </a>

        <h1 class="mt-10 font-display text-4xl font-bold tracking-tight">Política de privacidade</h1>
        <p class="mt-2 text-sm text-ink-muted">Atualizada em 3 de novembro de 2026</p>

        <div class="mt-8 space-y-6 leading-relaxed text-ink">
            <p>O Faro é um projeto de portfólio que usa inteligência artificial para encontrar vagas de estágio e júnior compatíveis com o seu currículo. Esta página explica, de forma direta, o que fazemos com os seus dados.</p>

            <section>
                <h2 class="font-display text-xl font-semibold">Quais dados guardamos</h2>
                <ul class="mt-2 list-disc space-y-1 pl-6">
                    <li>Nome, e-mail e senha (a senha é guardada criptografada, ninguém consegue lê-la).</li>
                    <li>O currículo em PDF que você enviar e o texto extraído dele.</li>
                    <li>O perfil profissional montado a partir do currículo: habilidades, nível, área, cidade e preferências.</li>
                    <li>As vagas que você salvar e o andamento das suas candidaturas.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">Para que usamos</h2>
                <p class="mt-2">Só para montar o seu perfil e recomendar vagas. Não vendemos, não compartilhamos e não usamos seus dados para publicidade.</p>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">Onde a IA roda</h2>
                <p class="mt-2">A leitura do currículo é feita por um modelo de IA aberto que roda num servidor próprio do projeto. O seu currículo <strong>não é enviado</strong> para serviços de IA de terceiros, como ChatGPT ou Gemini.</p>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">Seus direitos (LGPD)</h2>
                <p class="mt-2">Você pode ver e corrigir o seu perfil a qualquer momento na página "Meu perfil". Também pode excluir a sua conta por lá: isso apaga definitivamente seus dados, currículos e candidaturas.</p>
            </section>

            <section>
                <h2 class="font-display text-xl font-semibold">Contato</h2>
                <p class="mt-2">Dúvidas sobre os seus dados: <a href="mailto:contato@marcusmoreira.com.br" class="font-medium text-accent-text underline">contato@marcusmoreira.com.br</a>.</p>
            </section>
        </div>
    </div>
</x-layouts.app>
