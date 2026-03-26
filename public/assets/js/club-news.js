(() => {
  const pageEl = document.getElementById("club-news-page");
  if (!pageEl) return;

  const state = {
    loading: false,
    articlesPublic: [],
    articlesClub: [],
  };

  const els = {
    alert: document.getElementById("club-news-alert"),
    refreshBtn: document.getElementById("club-news-refresh-btn"),
    countPublic: document.getElementById("club-news-count-public"),
    countClub: document.getElementById("club-news-count-club"),
    listPublic: document.getElementById("club-news-list-public"),
    listClub: document.getElementById("club-news-list-club"),
    emptyPublic: document.getElementById("club-news-empty-public"),
    emptyClub: document.getElementById("club-news-empty-club"),

    createForm: document.getElementById("club-news-create-form"),
    resetBtn: document.getElementById("club-news-reset-btn"),
    submitBtn: document.getElementById("club-news-submit-btn"),
    submitSpinner: document.getElementById("club-news-submit-spinner"),

    editModalEl: document.getElementById("club-news-edit-modal"),
    editForm: document.getElementById("club-news-edit-form"),
    editId: document.getElementById("club-news-edit-id"),
    editTitle: document.getElementById("club-news-edit-title"),
    editAudience: document.getElementById("club-news-edit-audience"),
    editContent: document.getElementById("club-news-edit-content"),
    editSaveBtn: document.getElementById("club-news-edit-save-btn"),
    editSpinner: document.getElementById("club-news-edit-spinner"),
  };

  const cfg = window.clubNewsPage || {};
  const canManage = !!cfg.canManage;

  function showAlert(type, message) {
    if (!els.alert) return;
    els.alert.className = `alert alert-${type}`;
    els.alert.textContent = message;
    els.alert.classList.remove("d-none");
  }

  function hideAlert() {
    if (!els.alert) return;
    els.alert.classList.add("d-none");
  }

  function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = String(str ?? "");
    return div.innerHTML;
  }

  function formatDate(dtStr) {
    if (!dtStr) return "";
    const d = new Date(dtStr);
    if (Number.isNaN(d.getTime())) return String(dtStr);
    return d.toLocaleString("fr-FR", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function unwrapResponse(json) {
    if (!json) return null;
    // Accepte { public:[], club:[] } ou { data: { public:[], club:[] } }
    if (json.public || json.club) return json;
    if (json.data && (json.data.public || json.data.club)) return json.data;
    return json;
  }

  function setLoading(isLoading) {
    state.loading = isLoading;
    if (els.refreshBtn) els.refreshBtn.disabled = isLoading;
    if (els.submitBtn) els.submitBtn.disabled = isLoading;
    if (els.submitSpinner) els.submitSpinner.classList.toggle("d-none", !isLoading);
  }

  function setEditLoading(isLoading) {
    if (els.editSaveBtn) els.editSaveBtn.disabled = isLoading;
    if (els.editSpinner) els.editSpinner.classList.toggle("d-none", !isLoading);
  }

  function renderList(listEl, emptyEl, articles) {
    if (!listEl) return;
    listEl.innerHTML = "";
    const has = Array.isArray(articles) && articles.length > 0;
    if (emptyEl) emptyEl.classList.toggle("d-none", has);
    if (!has) return;

    for (const a of articles) {
      const created = a.created_at ? formatDate(a.created_at) : "";
      const updated = a.updated_at ? formatDate(a.updated_at) : "";
      const metaParts = [];
      if (a.author_name) metaParts.push(`Par ${escapeHtml(a.author_name)}`);
      if (created) metaParts.push(`Créé le ${escapeHtml(created)}`);
      if (updated && updated !== created) metaParts.push(`Modifié le ${escapeHtml(updated)}`);

      const audienceBadge =
        a.audience === "club"
          ? `<span class="badge bg-secondary">Mon club</span>`
          : `<span class="badge bg-primary">Public</span>`;

      const att = a.attachment || null;
      const attUrl = att && att.url ? String(att.url) : "";
      const attName = att && att.originalName ? String(att.originalName) : "";
      const attMime = att && att.mimeType ? String(att.mimeType) : "";
      const attLower = attName.toLowerCase();
      const isImageByExt =
        attUrl &&
        (attLower.endsWith(".jpg") ||
          attLower.endsWith(".jpeg") ||
          attLower.endsWith(".png") ||
          attLower.endsWith(".gif") ||
          attLower.endsWith(".webp") ||
          attLower.endsWith(".bmp"));
      const isImage = attUrl && (attMime.startsWith("image/") || isImageByExt);
      const isPdf = attUrl && (attMime === "application/pdf" || attName.toLowerCase().endsWith(".pdf"));

      const attachment = attName
        ? `
            <div class="mt-3">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="text-muted small">
                  <i class="fas fa-paperclip me-1"></i>
                  ${escapeHtml(attName)}
                </div>
                ${
                  attUrl
                    ? `<a class="btn btn-sm btn-outline-secondary" href="${escapeHtml(attUrl)}" target="_blank" rel="noopener noreferrer">Ouvrir</a>`
                    : ""
                }
              </div>
              ${
                isImage
                  ? `<div class="mt-2">
                       <a href="${escapeHtml(attUrl)}" target="_blank" rel="noopener noreferrer">
                         <img src="${escapeHtml(attUrl)}" alt="${escapeHtml(attName)}" class="img-fluid rounded border" style="max-height: 420px; object-fit: contain;">
                       </a>
                     </div>`
                  : isPdf
                    ? `<div class="mt-2 text-muted small">Aperçu non intégré (PDF). Utilise “Ouvrir”.</div>`
                    : ""
              }
            </div>
          `
        : "";

      const actions =
        canManage && a.id
          ? `
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${escapeHtml(a.id)}">
                <i class="fas fa-edit me-1"></i> Éditer
              </button>
              <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${escapeHtml(a.id)}">
                <i class="fas fa-trash me-1"></i> Supprimer
              </button>
            </div>
          `
          : "";

      const likesCount = Number(a.likes_count || 0);
      const userLiked = !!a.user_liked;
      const targetArrowIcon = `
        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2.2"></circle>
          <circle cx="12" cy="12" r="4.5" fill="none" stroke="currentColor" stroke-width="2.2"></circle>
          <path d="M12 12 L18.2 5.8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path>
          <path d="M18.2 5.8 L16.1 6.1 L17.9 7.9 Z" fill="currentColor"></path>
        </svg>`;
      const likeBtn = a.id
        ? `<button class="btn btn-sm ${userLiked ? "btn-danger" : "btn-outline-danger"} d-inline-flex align-items-center gap-1" data-action="like" data-id="${escapeHtml(a.id)}" title="J'aime">
             <span class="d-inline-flex align-items-center">${targetArrowIcon}</span>
             <span>${likesCount}</span>
           </button>`
        : "";

      const comments = Array.isArray(a.comments) ? a.comments : [];
      const commentsHtml = comments.length
        ? comments
            .map(
              (c) =>
                `<div class="small border rounded p-2 mb-2">
                  <div class="fw-semibold">${escapeHtml(c.user_name || "Utilisateur")}</div>
                  <div>${escapeHtml(c.content || "").replace(/\n/g, "<br>")}</div>
                </div>`
            )
            .join("")
        : `<div class="text-muted small">Aucun commentaire.</div>`;

      const commentForm = canManage && a.id
        ? `<div class="input-group input-group-sm mt-2">
             <input type="text" class="form-control" data-comment-input-id="${escapeHtml(a.id)}" placeholder="Ajouter un commentaire...">
             <button class="btn btn-outline-primary" data-action="comment" data-id="${escapeHtml(a.id)}">Commenter</button>
           </div>`
        : "";

      const title = a.title ? `<h3 class="h6 mb-2">${escapeHtml(a.title)}</h3>` : "";
      const content = a.content ? escapeHtml(a.content).replace(/\n/g, "<br>") : "";

      const card = document.createElement("div");
      card.className = "card border-0 shadow-sm";
      card.innerHTML = `
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                ${audienceBadge}
                ${metaParts.length ? `<div class="text-muted small">${metaParts.join(" • ")}</div>` : ""}
              </div>
              ${title}
              <div style="white-space: normal;">${content}</div>
              ${attachment}
              <div class="mt-3 d-flex align-items-center gap-2">
                ${likeBtn}
                <span class="text-muted small">${comments.length} commentaire(s)</span>
              </div>
              <div class="mt-2">${commentsHtml}</div>
              ${commentForm}
            </div>
            ${actions ? `<div class="flex-shrink-0">${actions}</div>` : ""}
          </div>
        </div>
      `;
      listEl.appendChild(card);
    }
  }

  function render() {
    if (els.countPublic) els.countPublic.textContent = String(state.articlesPublic.length);
    if (els.countClub) els.countClub.textContent = String(state.articlesClub.length);
    renderList(els.listPublic, els.emptyPublic, state.articlesPublic);
    renderList(els.listClub, els.emptyClub, state.articlesClub);
  }

  async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
      credentials: "same-origin",
      ...options,
      headers: {
        ...(options.headers || {}),
      },
    });

    const contentType = res.headers.get("content-type") || "";
    const isJson = contentType.includes("application/json");
    const body = isJson ? await res.json().catch(() => null) : await res.text().catch(() => "");

    if (!res.ok) {
      const msg =
        (body && (body.message || body.error)) ||
        (typeof body === "string" && body) ||
        `Erreur HTTP ${res.status}`;
      const err = new Error(msg);
      err.status = res.status;
      err.body = body;
      throw err;
    }

    return body;
  }

  async function load() {
    hideAlert();
    setLoading(true);
    try {
      const json = await apiFetch("/api/club-news", { method: "GET" });
      const data = unwrapResponse(json) || {};
      state.articlesPublic = Array.isArray(data.public) ? data.public : [];
      state.articlesClub = Array.isArray(data.club) ? data.club : [];
      render();
    } catch (e) {
      const status = e && typeof e.status === "number" ? e.status : null;
      if (status === 404) {
        showAlert(
          "warning",
          "L’API des actualités n’est pas encore disponible sur ce serveur. (Route /api/club-news introuvable.)"
        );
      } else if (status === 401 || status === 403) {
        showAlert("warning", "Vous n’êtes pas autorisé à consulter ces actualités.");
      } else {
        showAlert("danger", `Impossible de charger les actualités. ${e.message || ""}`.trim());
      }
      render();
    } finally {
      setLoading(false);
    }
  }

  async function createArticle(formEl) {
    if (!formEl) return;
    hideAlert();
    setLoading(true);
    try {
      const fd = new FormData(formEl);
      await apiFetch("/api/club-news", { method: "POST", body: fd });
      formEl.reset();
      showAlert("success", "Actualité publiée.");
      await load();
    } catch (e) {
      const status = e && typeof e.status === "number" ? e.status : null;
      if (status === 401 || status === 403) {
        showAlert("warning", "Vous n’êtes pas autorisé à publier.");
      } else {
        showAlert("danger", `Publication impossible. ${e.message || ""}`.trim());
      }
    } finally {
      setLoading(false);
    }
  }

  function openEditModal(article) {
    if (!els.editModalEl || !els.editId || !els.editContent || !els.editAudience || !els.editTitle) return;
    els.editId.value = article.id || "";
    els.editTitle.value = article.title || "";
    els.editContent.value = article.content || "";
    els.editAudience.value = article.audience || "public";

    if (window.bootstrap && window.bootstrap.Modal) {
      const modal = window.bootstrap.Modal.getOrCreateInstance(els.editModalEl);
      modal.show();
    }
  }

  async function saveEdit() {
    hideAlert();
    if (!els.editId) return;
    const id = els.editId.value;
    if (!id) return;

    setEditLoading(true);
    try {
      const payload = {
        title: (els.editTitle && els.editTitle.value) || "",
        content: (els.editContent && els.editContent.value) || "",
        audience: (els.editAudience && els.editAudience.value) || "public",
      };
      await apiFetch(`/api/club-news/${encodeURIComponent(id)}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      showAlert("success", "Actualité mise à jour.");
      if (window.bootstrap && window.bootstrap.Modal && els.editModalEl) {
        const modal = window.bootstrap.Modal.getOrCreateInstance(els.editModalEl);
        modal.hide();
      }
      await load();
    } catch (e) {
      const status = e && typeof e.status === "number" ? e.status : null;
      if (status === 401 || status === 403) {
        showAlert("warning", "Vous n’êtes pas autorisé à modifier cette actualité.");
      } else {
        showAlert("danger", `Modification impossible. ${e.message || ""}`.trim());
      }
    } finally {
      setEditLoading(false);
    }
  }

  async function deleteArticle(id) {
    hideAlert();
    if (!id) return;
    if (!window.confirm("Supprimer cette actualité ?")) return;
    setLoading(true);
    try {
      await apiFetch(`/api/club-news/${encodeURIComponent(id)}`, { method: "DELETE" });
      showAlert("success", "Actualité supprimée.");
      await load();
    } catch (e) {
      const status = e && typeof e.status === "number" ? e.status : null;
      if (status === 401 || status === 403) {
        showAlert("warning", "Vous n’êtes pas autorisé à supprimer cette actualité.");
      } else {
        showAlert("danger", `Suppression impossible. ${e.message || ""}`.trim());
      }
    } finally {
      setLoading(false);
    }
  }

  async function likeArticle(id) {
    hideAlert();
    if (!id) return;
    try {
      await apiFetch(`/api/club-news/${encodeURIComponent(id)}/likes`, { method: "POST" });
      await load();
    } catch (e) {
      showAlert("danger", `Like impossible. ${e.message || ""}`.trim());
    }
  }

  async function addComment(id, content) {
    hideAlert();
    if (!id) return;
    const txt = String(content || "").trim();
    if (!txt) return;
    try {
      await apiFetch(`/api/club-news/${encodeURIComponent(id)}/comments`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ content: txt }),
      });
      await load();
    } catch (e) {
      showAlert("danger", `Commentaire impossible. ${e.message || ""}`.trim());
    }
  }

  function findArticleById(id) {
    return (
      state.articlesPublic.find((a) => a && a.id === id) ||
      state.articlesClub.find((a) => a && a.id === id) ||
      null
    );
  }

  // Events
  if (els.refreshBtn) els.refreshBtn.addEventListener("click", () => load());

  if (els.resetBtn && els.createForm) {
    els.resetBtn.addEventListener("click", () => {
      els.createForm.reset();
      hideAlert();
    });
  }

  if (els.createForm) {
    els.createForm.addEventListener("submit", (e) => {
      e.preventDefault();
      createArticle(els.createForm);
    });
  }

  if (els.listPublic) {
    els.listPublic.addEventListener("click", (e) => {
      const btn = e.target && e.target.closest ? e.target.closest("button[data-action]") : null;
      if (!btn) return;
      const action = btn.getAttribute("data-action");
      const id = btn.getAttribute("data-id");
      if (!id) return;
      if (action === "edit") {
        const a = findArticleById(id);
        if (a) openEditModal(a);
      } else if (action === "delete") {
        deleteArticle(id);
      } else if (action === "like") {
        likeArticle(id);
      } else if (action === "comment") {
        const input = els.listPublic.querySelector(`input[data-comment-input-id="${CSS.escape(id)}"]`);
        addComment(id, input ? input.value : "");
      }
    });
  }

  if (els.listClub) {
    els.listClub.addEventListener("click", (e) => {
      const btn = e.target && e.target.closest ? e.target.closest("button[data-action]") : null;
      if (!btn) return;
      const action = btn.getAttribute("data-action");
      const id = btn.getAttribute("data-id");
      if (!id) return;
      if (action === "edit") {
        const a = findArticleById(id);
        if (a) openEditModal(a);
      } else if (action === "delete") {
        deleteArticle(id);
      } else if (action === "like") {
        likeArticle(id);
      } else if (action === "comment") {
        const input = els.listClub.querySelector(`input[data-comment-input-id="${CSS.escape(id)}"]`);
        addComment(id, input ? input.value : "");
      }
    });
  }

  if (els.editSaveBtn) els.editSaveBtn.addEventListener("click", () => saveEdit());

  // Init
  load();
})();

