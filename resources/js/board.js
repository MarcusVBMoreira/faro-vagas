import Sortable from 'sortablejs';

/*
 * Kanban de candidaturas: arrastar entre colunas grava a nova ordem da coluna de destino
 * (e da de origem). Sem JavaScript, o seletor "Mover" de cada cartão faz o mesmo.
 */
export function initBoard(board) {
    const url = board.dataset.reorderUrl;

    const save = (column) => window.axios.post(url, {
        status: column.dataset.column,
        ids: [...column.querySelectorAll(':scope > [data-id]')].map((li) => Number(li.dataset.id)),
    });

    const refreshCounts = () => board.querySelectorAll('[data-column]').forEach((column) => {
        const counter = document.querySelector(`[data-count="${column.dataset.column}"]`);
        if (counter) counter.textContent = column.querySelectorAll(':scope > [data-id]').length;
    });

    board.querySelectorAll('[data-column]').forEach((column) => {
        Sortable.create(column, {
            group: 'board',
            animation: 150,
            filter: 'details, a, button, select, textarea, input',
            preventOnFilter: false,
            ghostClass: 'opacity-40',
            onEnd: async (event) => {
                refreshCounts();
                try {
                    await save(event.to);
                    if (event.from !== event.to) await save(event.from);
                } catch (e) {
                    window.location.reload(); // volta ao estado salvo
                }
            },
        });
    });
}
