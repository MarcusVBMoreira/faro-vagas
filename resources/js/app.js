import './bootstrap';
import { initBoard } from './board';

const board = document.querySelector('[data-board]');
if (board) initBoard(board);

/*
 * Tela de carregamento: aparece por cima de tudo quando um formulário é enviado
 * (salvar perfil, enviar foto ou currículo, entrar...) ou quando um link com
 * data-loading é clicado. Bloqueia cliques e rolagem até a próxima página chegar,
 * e evita envio duplo.
 */
const overlay = document.getElementById('page-loading');

function showLoading(text) {
    if (!overlay) return;
    overlay.querySelector('[data-loading-text]').textContent = text || overlay.dataset.default;
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    overlay.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('overflow-hidden');
    document.body.setAttribute('aria-busy', 'true');
}

function hideLoading() {
    if (!overlay) return;
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    overlay.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('overflow-hidden');
    document.body.removeAttribute('aria-busy');
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || form.hasAttribute('data-no-loading')) return;

    showLoading(form.dataset.loading);
    // Evita clicar duas vezes enquanto a página carrega
    setTimeout(() => form.querySelectorAll('button[type="submit"], button:not([type])').forEach((b) => (b.disabled = true)), 0);
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-loading]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank') return;
    showLoading(link.dataset.loading);
});

// Ao voltar pelo botão "voltar" do navegador, a página vem do cache: some com a tela.
window.addEventListener('pageshow', hideLoading);
