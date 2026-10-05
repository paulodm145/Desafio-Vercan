import Quill from 'quill';

export function inicializarEditorObservacoes() {
    const textarea = document.getElementById('observacoes');
    const elementoEditor = document.getElementById('editor-observacoes');

    // Mesma configuração de toolbar do tema snow usada em forms/editors.html
    // da pasta do AdminLTE que serve de base para o projeto.
    const editor = new Quill(elementoEditor, {
        theme: 'snow',
        placeholder: 'Digite aqui as observações...',
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ indent: '-1' }, { indent: '+1' }],
                ['blockquote', 'code-block'],
                ['link', 'clean'],
            ],
        },
    });

    if (textarea.value) {
        editor.clipboard.dangerouslyPasteHTML(textarea.value);
    }

    editor.on('text-change', () => {
        textarea.value = editor.root.innerHTML;
    });

    // Quill renderiza o seletor de nível de título sem nome acessível.
    document.querySelectorAll('.ql-header .ql-picker-label').forEach((label) => {
        label.setAttribute('aria-label', 'Formato de título');
    });
}
