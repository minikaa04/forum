// ============================================
// SözlükForum — Client-side JavaScript
// ============================================

// Theme management
(function() {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    updateThemeUI(saved);
})();

function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateThemeUI(next);
}

function updateThemeUI(theme) {
    const icon = document.getElementById('themeIcon');
    const text = document.getElementById('themeText');
    if (icon) {
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
    if (text) {
        text.textContent = theme === 'dark' ? 'Aydınlık Mod' : 'Karanlık Mod';
    }
}

// Sidebar toggle (mobile)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

// Close sidebar on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    }
});

// Vote (like/dislike) via AJAX
function vote(commentId, voteType) {
    const formData = new FormData();
    formData.append('comment_id', commentId);
    formData.append('vote', voteType);

    fetch('vote.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) return;

        // Update counts
        const likesEl = document.getElementById('likes-' + commentId);
        const dislikesEl = document.getElementById('dislikes-' + commentId);
        if (likesEl) likesEl.textContent = data.likes;
        if (dislikesEl) dislikesEl.textContent = data.dislikes;

        // Update button states
        const comment = document.getElementById('comment-' + commentId);
        if (comment) {
            const btns = comment.querySelectorAll(':scope > .comment-actions .vote-btn');
            btns.forEach(btn => btn.classList.remove('voted'));
            if (data.userVote === 1) {
                btns[0]?.classList.add('voted');
            } else if (data.userVote === -1) {
                btns[1]?.classList.add('voted');
            }
        }
    })
    .catch(err => console.error('Vote error:', err));
}

// Reply form toggle
function showReplyForm(commentId) {
    const form = document.getElementById('reply-form-' + commentId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            form.querySelector('textarea')?.focus();
        }
    }
}

function hideReplyForm(commentId) {
    const form = document.getElementById('reply-form-' + commentId);
    if (form) form.style.display = 'none';
}

// Form validation — prevent empty submissions
function validateForm(form) {
    const textareas = form.querySelectorAll('textarea[required]');
    const inputs = form.querySelectorAll('input[required]');
    let valid = true;

    textareas.forEach(ta => {
        if (ta.value.trim() === '') {
            ta.style.borderColor = '#e17055';
            ta.classList.add('shake');
            setTimeout(() => {
                ta.style.borderColor = '';
                ta.classList.remove('shake');
            }, 1500);
            valid = false;
        }
    });

    inputs.forEach(inp => {
        if (inp.value.trim() === '') {
            inp.style.borderColor = '#e17055';
            inp.classList.add('shake');
            setTimeout(() => {
                inp.style.borderColor = '';
                inp.classList.remove('shake');
            }, 1500);
            valid = false;
        }
    });

    return valid;
}

// Add shake animation via JS
const shakeStyle = document.createElement('style');
shakeStyle.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20% { transform: translateX(-6px); }
        40% { transform: translateX(6px); }
        60% { transform: translateX(-4px); }
        80% { transform: translateX(4px); }
    }
    .shake { animation: shake 0.4s ease; }
`;
document.head.appendChild(shakeStyle);

// Close sidebar when clicking on nav item (mobile)
document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
    if (!item.classList.contains('theme-toggle')) {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('open')) {
                    toggleSidebar();
                }
            }
        });
    }
});
