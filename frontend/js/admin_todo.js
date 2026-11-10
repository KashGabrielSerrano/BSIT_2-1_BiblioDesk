
document.addEventListener("DOMContentLoaded", () => {
  const KEY = "bibliodesk_todos";
  const MAX_ON_CARD = 4;
  const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

  const $ = (id) => document.getElementById(id);

  const cardList = $("todoList");
  if (!cardList) return; // not the admin dashboard

  const manageList = $("todoManageList");
  const form = $("todoForm");
  const modal = $("todoModal");
  const DEFAULTS = [];

  // ---------- helpers ----------
  const esc = (s) =>
    String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c],
    );

  const newId = () => `t-${Date.now()}${Math.random().toString(36).slice(2, 6)}`;

  const dateParts = (iso) => {
    const [y, m, d] = String(iso).split("-").map(Number);
    return { day: d || "", month: MONTHS[(m || 1) - 1] };
  };

  const load = () => {
    let raw = null;
    try {
      raw = JSON.parse(localStorage.getItem(KEY));
    } catch (e) {
      raw = null;
    }
    if (!Array.isArray(raw)) return DEFAULTS.map((t) => ({ ...t }));
    return raw.filter((t) => t && t.title && t.date);
  };

  let items = load();
  let editingId = null;

  const save = () => {
    try {
      localStorage.setItem(KEY, JSON.stringify(items));
    } catch (e) {
      /* storage unavailable */
    }
  };

  const sorted = () =>
    [...items].sort((a, b) => a.date.localeCompare(b.date) || a.title.localeCompare(b.title));


  const dateBox = (t) => {
    const { day, month } = dateParts(t.date);
    return `<span class="dateBox rounded-2 text-center">
              <span class="dateDay">${esc(day)}</span>
              <span class="dateMonth">${esc(month)}</span>
            </span>`;
  };

  const textBlock = (t) =>
    `<span>
       <span class="d-block fw-semibold small">${esc(t.title)}</span>
       ${t.desc ? `<span class="d-block todoDesc">${esc(t.desc)}</span>` : ""}
     </span>`;

  const renderCard = () => {
    const list = sorted();

    if (!list.length) {
      cardList.innerHTML = '<li class="todoEmpty">No to-do items yet. Click + to add one.</li>';
      return;
    }

    let html = list
      .slice(0, MAX_ON_CARD)
      .map((t) => `<li class="todoItem d-flex align-items-center gap-3">${dateBox(t)}${textBlock(t)}</li>`)
      .join("");

    const more = list.length - MAX_ON_CARD;
    if (more > 0) {
      html += `<li><button type="button" class="todoMoreBtn" data-bs-toggle="modal" data-bs-target="#todoModal">+${more} more</button></li>`;
    }

    cardList.innerHTML = html;
  };

  const renderManage = () => {
    const list = sorted();

    if (!list.length) {
      manageList.innerHTML = '<li class="qaCount small py-2">Nothing here yet. Add your first to-do below.</li>';
      return;
    }

    manageList.innerHTML = list
      .map(
        (t) => `
        <li class="todoManageRow d-flex align-items-center gap-3${t.id === editingId ? " isEditing" : ""}">
          ${dateBox(t)}
          <span class="flex-grow-1">${textBlock(t)}</span>
          <button type="button" class="btn btn-sm btn-outline-dark" data-act="edit" data-id="${esc(t.id)}">Edit</button>
          <button type="button" class="btn btn-sm btn-outline-danger" data-act="delete" data-id="${esc(t.id)}">Delete</button>
        </li>`,
      )
      .join("");
  };

  const render = () => {
    renderCard();
    renderManage();
  };


  const setError = (input, message) => {
    input.classList.toggle("is-invalid", !!message);
    const fb = input.parentElement.querySelector(".invalid-feedback");
    if (fb) fb.textContent = message || "";
    return !message;
  };

  const resetForm = () => {
    editingId = null;
    form.reset();
    form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
    $("todoFormTitle").textContent = "Add a to-do";
    $("todoSave").textContent = "Add to-do";
    $("todoCancelEdit").classList.add("d-none");
  };

  const startEdit = (item) => {
    editingId = item.id;
    $("todoDate").value = item.date;
    $("todoTitle").value = item.title;
    $("todoDesc").value = item.desc || "";
    $("todoFormTitle").textContent = "Edit to-do";
    $("todoSave").textContent = "Save changes";
    $("todoCancelEdit").classList.remove("d-none");
    renderManage();
    $("todoTitle").focus();
  };

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const date = $("todoDate");
    const title = $("todoTitle");
    const desc = $("todoDesc");

    let valid = true;
    valid = setError(date, date.value ? "" : "Please choose a date.") && valid;
    valid = setError(title, title.value.trim() ? "" : "Please enter a title.") && valid;
    if (!valid) return;

    const data = { date: date.value, title: title.value.trim(), desc: desc.value.trim() };
    const existing = editingId && items.find((t) => t.id === editingId);

    if (existing) Object.assign(existing, data);
    else items.push({ id: newId(), ...data });

    save();
    resetForm();
    render();
  });

  $("todoCancelEdit").addEventListener("click", () => {
    resetForm();
    renderManage();
  });


  manageList.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-act]");
    if (!btn) return;

    const item = items.find((t) => t.id === btn.dataset.id);
    if (!item) return;

    if (btn.dataset.act === "edit") {
      startEdit(item);
    } else if (window.confirm(`Delete "${item.title}"?`)) {
      items = items.filter((t) => t.id !== item.id);
      if (editingId === item.id) resetForm();
      save();
      render();
    }
  });


  modal.addEventListener("shown.bs.modal", () => $("todoTitle").focus());
  modal.addEventListener("hidden.bs.modal", () => {
    resetForm();
    renderManage();
  });


  render();
});