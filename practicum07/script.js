'use strict';

const API = 'api.php';

const $search  = document.getElementById('search');
const $results = document.getElementById('results');
const $count   = document.getElementById('count');
const $status  = document.getElementById('status');
const $form    = document.getElementById('add-form');
const $errors  = document.getElementById('form-errors');

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));
}

function showStatus(text, ok = true) {
    $status.textContent = text;
    $status.className   = 'msg ' + (ok ? 'msg--ok' : 'msg--error');
    $status.hidden      = false;
}

function hideStatus() { $status.hidden = true; }

function buildCardElement(book) {
    const statusClass = book.is_available ? 'status--available' : 'status--issued';
    const label       = book.is_available ? 'Доступна' : 'Видана';

    const ebookRows = book.is_ebook
        ? `<li><span>Формат файлу:</span> ${esc(book.format)}</li>
           <li><span>Розмір файлу:</span> ${Number(book.file_size_mb).toFixed(1)} МБ</li>`
        : '';

    const toggleBtn = book.is_available
        ? `<button type="button" class="btn-checkout" data-action="checkout">Видати</button>`
        : `<button type="button" class="btn-return"   data-action="return">Повернути</button>`;

    const article = document.createElement('article');
    article.className = 'card ' + statusClass;
    article.dataset.id = book.id;

    article.innerHTML = `
        <h2 class="card__heading">
            «${esc(book.title)}» — <em>${esc(book.author)}</em>
        </h2>
        <ul class="card__meta">
            <li><span>Рік видання:</span> ${esc(book.year)}</li>
            <li><span>ISBN:</span> ${esc(book.isbn_pretty || book.isbn)}</li>
            ${ebookRows}
        </ul>
        <span class="card__status ${statusClass}">${label}</span>
        <div class="card__actions">
            ${toggleBtn}
            <a class="btn-edit" href="edit.php?id=${encodeURIComponent(book.id)}">Редагувати</a>
            <a class="btn-delete" href="delete.php?id=${encodeURIComponent(book.id)}">Видалити</a>
        </div>`;

    const btnDelete = article.querySelector('.btn-delete');
    btnDelete.addEventListener('click', (e) => {
        if (!confirm(`Видалити книгу «${book.title}»?`)) {
            e.preventDefault();
        }
    });

    const toggle = article.querySelector('.btn-checkout, .btn-return');
    if (toggle) {
        toggle.addEventListener('click', () => toggleAvailability(book, toggle.dataset.action));
    }

    return article;
}

async function toggleAvailability(book, action) {
    try {
        const res = await fetch(
            `${API}?resource=books&id=${encodeURIComponent(book.id)}&action=${action}`,
            { method: 'POST', headers: { 'Accept': 'application/json' } }
        );

        const data = await res.json();

        if (!res.ok || !data.success) {
            throw new Error(data.error || `HTTP ${res.status}`);
        }

        showStatus(data.data.message, true);

        const oldCard = $results.querySelector(`article[data-id="${book.id}"]`);
        if (oldCard) {
            oldCard.replaceWith(buildCardElement(data.data.book));
        }

    } catch (err) {
        console.error(err);
        showStatus('Помилка: ' + err.message, false);
    }
}

async function loadBooks(q = '') {
    hideStatus();
    try {
        const res = await fetch(`${API}?resource=books`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();

        if (!data.success) {
            throw new Error(data.error || 'Невідома помилка сервера');
        }

        let books = data.data.books;

        if (q !== '') {
            const needle = q.toLowerCase();
            books = books.filter(b =>
                String(b.title).toLowerCase().includes(needle)
            );
        }

        $count.textContent = books.length;
        $results.innerHTML = '';

        if (books.length === 0) {
            $results.innerHTML = '<p>Записів не знайдено.</p>';
            return;
        }

        const frag = document.createDocumentFragment();
        books.forEach(b => frag.appendChild(buildCardElement(b)));
        $results.appendChild(frag);

    } catch (err) {
        console.error(err);
        $results.innerHTML = '';
        $count.textContent = '0';
        showStatus('Помилка завантаження: ' + err.message, false);
    }
}

let searchTimer = null;
$search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadBooks($search.value.trim()), 250);
});

$form.addEventListener('submit', async (e) => {
    e.preventDefault();
    $errors.hidden = true;
    $errors.innerHTML = '';

    const fd = new FormData($form);
    const payload = {
        title:        fd.get('title')  || '',
        author:       fd.get('author') || '',
        year:         fd.get('year')   || '',
        isbn:         fd.get('isbn')   || '',
        is_available: fd.get('is_available') ? 1 : 0,
    };

    try {
        const res = await fetch(`${API}?resource=books`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });

        const data = await res.json();

        if (res.status === 400 && data.errors) {
            $errors.innerHTML = Object.values(data.errors)
                .map(m => `<li>${esc(m)}</li>`).join('');
            $errors.hidden = false;
            return;
        }
        if (!res.ok || !data.success) {
            throw new Error(data.error || `HTTP ${res.status}`);
        }

        $form.reset();

        const emptyMsg = $results.querySelector('p');
        if (emptyMsg) emptyMsg.remove();

        $results.appendChild(buildCardElement(data.data));
        $count.textContent = String(Number($count.textContent) + 1);

        showStatus(`Книгу «${data.data.title}» додано.`, true);

    } catch (err) {
        console.error(err);
        showStatus('Помилка додавання: ' + err.message, false);
    }
});

document.addEventListener('DOMContentLoaded', () => loadBooks());