// --- 1. 全ての関数を window オブジェクトに紐付けて定義 ---

// ページ切り替えのメイン関数
window.loadPage = async function (page) {
    const contentArea = document.getElementById('app-content');
    if (!contentArea) return;

    contentArea.innerHTML = '<div class="loader">読み込み中...</div>';

    try {
        switch (page) {
            case 'list':
                await loadUserList();
                break;
            case 'mypage':
                renderMyPage();
                break;
            case 'settings':
                renderSettings();
                break;
            case 'liked-users':
                await loadLikedUsers();
                break;
            default:
                contentArea.innerHTML = `<h3>${page} ページは準備中です</h3>`;
        }
    } catch (error) {
        console.error('Page Load Error:', error);
        contentArea.innerHTML = '<div class="error">読み込みに失敗しました</div>';
    }
};

// ユーザー一覧表示
async function loadUserList() {
    const contentArea = document.getElementById('app-content');
    const response = await fetch('/api/users');
    const users = await response.json();

    if (!users || users.length === 0) {
        contentArea.innerHTML = '<p style="text-align:center; padding:20px;">新しい出会いはまだありません。</p>';
        return;
    }

    let html = '<div class="user-grid">';
    users.forEach(user => {
        html += `
            <div class="user-card">
                <div class="card-image-wrapper">
                    <img src="${user.profile_image || '/images/default.png'}" alt="profile">
                </div>
                <button class="like-btn" data-id="${user.id}">♥</button>
                <div class="card-info">
                    <h3>${user.username}</h3>
                    <p class="bio">${user.bio || ''}</p>
                </div>
            </div>`;
    });
    html += '</div>';
    contentArea.innerHTML = html;
    initLikeEvents();
}

// いいねした人一覧表示
async function loadLikedUsers() {
    const contentArea = document.getElementById('app-content');
    const response = await fetch('/api/likes/mine');
    const users = await response.json();

    let html = `
        <div class="settings-header" style="display:flex; align-items:center; padding:10px; background:#fff; border-bottom:1px solid #eee;">
            <button onclick="loadPage('mypage')" style="border:none; background:none; color:#3498db; cursor:pointer;">＜ 戻る</button>
            <h3 style="flex-grow:1; text-align:center; margin:0; padding-right:40px;">いいねした人</h3>
        </div>
        <div class="user-grid">`;

    if (users.length === 0) {
        html += '<p style="grid-column: 1/3; text-align:center; padding:20px;">まだ誰もいいねしていません。</p>';
    } else {
        users.forEach(user => {
            html += `
                <div class="user-card" id="card-${user.id}">
                    <div class="card-image-wrapper">
                        <img src="${user.profile_image || '/images/default.png'}">
                    </div>
                    <button class="remove-like-btn" onclick="removeLike('${user.id}')" style="position:absolute; top:10px; right:10px; background:rgba(255,255,255,0.8); border:none; border-radius:50%; width:30px; height:30px; color:#ff4757; cursor:pointer; font-weight:bold;">✕</button>
                    <div class="card-info">
                        <h3>${user.username}</h3>
                    </div>
                </div>`;
        });
    }
    html += '</div>';
    contentArea.innerHTML = html;
}

// マイページ描画
function renderMyPage() {
    const contentArea = document.getElementById('app-content');
    contentArea.innerHTML = `
        <div class="mypage-container">
            <div class="profile-header">
                <img src="/images/user_icon.png" class="profile-icon" onerror="this.src='https://via.placeholder.com/100'">
                <h2>マイページ</h2>
            </div>
            <ul class="menu-list">
                <li onclick="alert('準備中')"><span>プロフィール編集</span></li>
                <li onclick="alert('準備中')"><span>いいねをくれた人</span></li>
                <li onclick="loadPage('liked-users')"><span>いいねした人</span></li>
                <li onclick="loadPage('settings')"><span>各種設定</span></li>
            </ul>
        </div>`;
}

// 各種設定描画
function renderSettings() {
    const contentArea = document.getElementById('app-content');
    contentArea.innerHTML = `
        <div class="settings-container">
            <div class="settings-header" style="display:flex; align-items:center; padding:10px; border-bottom:1px solid #eee;">
                <button onclick="loadPage('mypage')" style="border:none; background:none; color:#3498db; cursor:pointer;">＜ 戻る</button>
                <h3 style="margin:0; flex-grow:1; text-align:center;">各種設定</h3>
            </div>
            <ul class="menu-list" style="margin-top:20px;">
                <li>通知設定</li>
                <li>メールアドレス変更</li>
                <li onclick="location.href='/logout'" style="color:#ff4757; font-weight:bold;">ログアウト</li>
                <li onclick="confirmWithdrawal()">退会手続き</li>
            </ul>
        </div>`;
}

// --- 2. 補助機能（イベント系） ---

function initLikeEvents() {
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.onclick = async function (e) {
            e.stopPropagation();
            if (this.classList.contains('is-active')) return;

            this.classList.add('is-active');
            const ripple = document.createElement('div');
            ripple.className = 'ripple';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);

            try {
                const response = await fetch('/api/like', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ to_user_id: this.dataset.id })
                });
                const result = await response.json();
                if (result.success) {
                    setTimeout(() => {
                        const card = this.closest('.user-card');
                        if (card) {
                            card.style.opacity = '0';
                            setTimeout(() => card.remove(), 400);
                        }
                    }, 800);
                }
            } catch (err) {
                this.classList.remove('is-active');
            }
        };
    });
}

window.removeLike = async function (userId) {
    if (!confirm('いいねを解除しますか？')) return;
    
    try {
        const response = await fetch('/api/like/remove', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                // もし環境によって必要ならこれも追加
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify({ to_user_id: userId }) // ここがPHP側と一致している必要がある！
        });

        const result = await response.json();
        
        if (result.success) {
            // 成功したら画面から消す
            const card = document.getElementById(`card-${userId}`);
            if (card) {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => card.remove(), 400);
            }
        } else {
            alert('解除に失敗しました: ' + (result.message || '不明なエラー'));
        }
    } catch (e) {
        console.error('Fetch Error:', e);
        alert('通信エラーが発生しました');
    }
};

window.confirmWithdrawal = function () {
    if (confirm("退会するとデータが全て削除されます。よろしいですか？")) {
        location.href = "/logout";
    }
};

// --- 3. 初期化 ---
document.addEventListener('DOMContentLoaded', () => {
    loadPage('list');

    document.querySelectorAll('.nav-item').forEach(button => {
        button.addEventListener('click', (e) => {
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            const btn = e.currentTarget;
            btn.classList.add('active');
            loadPage(btn.dataset.page);
        });
    });
});