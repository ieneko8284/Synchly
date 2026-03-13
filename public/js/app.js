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

            case 'matches':
                await loadMatches();
                break;

            // loadPage 関数の switch 文にケースを追加
            case 'received-likes':
                await loadReceivedLikes();
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
// マイページ描画（APIから名前を取得するように修正）
// マイページ描画
async function renderMyPage() {
    const contentArea = document.getElementById('app-content');
    contentArea.innerHTML = '<div class="loader">読み込み中...</div>';

    try {
        const response = await fetch('/api/my-profile');
        const result = await response.json();

        if (result.success) {
            const user = result.user;
            contentArea.innerHTML = `
                <div class="mypage-container">
                    <div class="profile-header">
                        <img src="${user.profile_image || '/images/user_icon.png'}" 
                             class="profile-icon" 
                             onerror="this.src='https://via.placeholder.com/100'">
                        <h2>${user.username}</h2>
                        <p style="color:#666; font-size:0.9rem;">${user.bio || '自己紹介はまだありません'}</p>
                    </div>
                    <ul class="menu-list">
                        <li onclick="alert('準備中')"><span>プロフィール編集</span></li>
                        <li onclick="loadPage('liked-users')"><span>いいねした人</span></li>
                        <li onclick="loadPage('settings')"><span>各種設定</span></li>
                    </ul>
                </div>`;
        } else {
            contentArea.innerHTML = '<p style="text-align:center; padding:20px;">プロフィールの取得に失敗しました。</p>';
        }
    } catch (error) {
        console.error('MyPage Load Error:', error);
        contentArea.innerHTML = '<div class="error">通信エラーが発生しました</div>';
    }
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

// マッチング演出を表示する関数
function showMatchAnimation(user) {
    // ★ここで画像のパスを判定する
    // user.profile_image があればそれを使い、なければダミー画像を使う
    const imgSrc = user.profile_image
        ? user.profile_image
        : 'https://placehold.jp/24/cc6666/ffffff/200x200.png?text=No+Image';

    const overlay = document.createElement('div');
    overlay.className = 'match-overlay';
    overlay.innerHTML = `
        <div class="match-content" style="text-align:center;">
            <h2 style="font-family: 'Arial Black', sans-serif;">MATCHED!</h2>
            <div class="match-photos">
                <img src="${imgSrc}" class="match-photo">
            </div>
            <p>${user.username}さんと繋がりました！</p>
            <button onclick="this.parentElement.parentElement.remove()" style="padding:10px 20px; border-radius:20px; border:none; background:#ff4757; color:white; cursor:pointer; margin-top:20px;">閉じる</button>
        </div>
    `;
    document.body.appendChild(overlay);
}

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
                    // ★ここから追加：マッチング判定
                    // initLikeEvents の中で呼ぶ
                    if (result.isMatched && result.matchedUser) {
                        // resultに相手の情報を含めて返せば、画像も出せるよ！
                        showMatchAnimation(result.matchedUser);
                    }

                    // カードを消す演出（元々の処理）
                    setTimeout(() => {
                        const card = this.closest('.user-card');
                        if (card) {
                            card.style.opacity = '0';
                            setTimeout(() => card.remove(), 400);
                        }
                    }, 800);
                }
            } catch (err) {
                console.error('Like failed:', err);
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

    checkNewMatches();

    document.querySelectorAll('.nav-item').forEach(button => {
        button.addEventListener('click', (e) => {
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            const btn = e.currentTarget;
            btn.classList.add('active');
            loadPage(btn.dataset.page);
        });
    });
});

// app.js に追加
async function loadReceivedLikes() {
    const contentArea = document.getElementById('app-content');
    const response = await fetch('/api/likes/received');
    const users = await response.json();

    let html = `
        <div class="settings-header" style="display:flex; align-items:center; padding:10px; background:#fff; border-bottom:1px solid #eee;">
            <button onclick="loadPage('mypage')" style="border:none; background:none; color:#3498db; cursor:pointer;">＜ 戻る</button>
            <h3 style="flex-grow:1; text-align:center; margin:0; padding-right:40px;">いいねをくれた人</h3>
        </div>
        <div class="user-grid">`;

    if (users.length === 0) {
        html += '<p style="grid-column: 1/3; text-align:center; padding:20px;">まだいいねは届いていないようです。</p>';
    } else {
        users.forEach(user => {
            html += `
                <div class="user-card">
                    <div class="card-image-wrapper">
                        <img src="${user.profile_image || '/images/default.png'}">
                    </div>
                    <button class="like-btn" data-id="${user.id}">♥</button>
                    <div class="card-info">
                        <h3>${user.username}</h3>
                        <p>${user.bio || ''}</p>
                    </div>
                </div>`;
        });
    }
    html += '</div>';
    contentArea.innerHTML = html;
    initLikeEvents(); // ここでいいねボタンを有効化！
}

// app.js 内の loadMatches 関数
async function loadMatches() {
    const contentArea = document.getElementById('app-content');
    contentArea.innerHTML = '<div class="loader">読み込み中...</div>';

    try {
        const response = await fetch('/api/matches');
        const users = await response.json();

        let html = `
            <div class="talk-list-header">
                <h2>トーク</h2>
            </div>
            <div class="talk-list">`;

        if (users.length === 0) {
            html += '<p class="empty-msg">まだマッチングした相手がいません。</p>';
        } else {
            users.forEach(user => {
                const imgSrc = user.profile_image || '/images/default.png';
                html += `
                    <div class="talk-item" onclick="startChat(${user.match_id}, '${user.username}')">
                        <img src="${imgSrc}" class="talk-avatar">
                        <div class="talk-info">
                            <div class="talk-name">${user.username}</div>
                            <div class="talk-preview">メッセージを送ってみましょう！</div>
                        </div>
                    </div>`;
            });
        }
        html += '</div>';
        contentArea.innerHTML = html;
    } catch (e) {
        contentArea.innerHTML = '<p>読み込みに失敗しました</p>';
    }
}

// 1. タイマーは一番外側で管理する
let chatTimer = null;

// 2. マッチチェック（これはバックグラウンドで動かす用）
async function checkNewMatches() {
    try {
        const response = await fetch('/api/check-new-matches');
        const newMatches = await response.json();
        if (newMatches && newMatches.length > 0) {
            newMatches.forEach(match => {
                showMatchAnimation(match);
                fetch('/api/mark-match-notified', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ from_user_id: match.id })
                });
            });
        }
    } catch (e) { console.error('Match check failed:', e); }
}

// 3. チャット開始（windowに紐付けてどこからでも呼べるようにする）
window.startChat = async function (matchId, partnerName) {
    // 【重要】新しいチャットを始める前に、古いタイマーを止める！
    if (chatTimer) clearInterval(chatTimer);

    const contentArea = document.getElementById('app-content');
    contentArea.innerHTML = '<div class="loader">読み込み中...</div>';

    try {
        const response = await fetch(`/api/chat/history?match_id=${matchId}`);
        const messages = await response.json();

        contentArea.innerHTML = `
            <div class="chat-container">
                <div class="chat-header">
                    <button onclick="stopChatAndBack()" class="back-btn">＜</button>
                    <h3>${partnerName}さん</h3>
                </div>
                <div id="chat-messages" class="chat-messages">${renderMessages(messages)}</div>
                <div class="chat-input-area">
                    <input type="text" id="message-input" placeholder="メッセージを入力...">
                    <button onclick="sendMessage(${matchId})" id="send-btn">送信</button>
                </div>
            </div>`;

        scrollChatToBottom();

        // 3秒おきに自動更新を開始
        chatTimer = setInterval(async () => {
            const res = await fetch(`/api/chat/history?match_id=${matchId}`);
            const newMsgs = await res.json();
            const el = document.getElementById('chat-messages');
            if (el) {
                el.innerHTML = renderMessages(newMsgs);
                // 必要に応じてスクロール処理をここに入れる
            }
        }, 3000);

    } catch (e) { console.error('Chat Load Error:', e); }
};

// 4. チャットを終了して戻る（タイマーを掃除する）
window.stopChatAndBack = function () {
    if (chatTimer) clearInterval(chatTimer);
    loadPage('matches');
};

// メッセージリストをHTMLにする補助関数
function renderMessages(messages) {
    if (messages.length === 0) return '<p style="text-align:center; color:#eee;">メッセージを送ってみよう！</p>';

    // ここで自分のIDと比較する
    // datasetに埋め込んでいない場合は、仮に session_id 的なものを変数に入れてね
    const myId = document.body.dataset.myId;

    return messages.map(msg => {
        const isMe = msg.sender_id === myId;
        return `
            <div class="message-bubble ${isMe ? 'sent' : 'received'}">
                <div class="bubble-content">${msg.message_text}</div>
                <span class="message-time">${msg.created_at}</span>
            </div>
        `;
    }).join('');
}
// メッセージ送信処理
window.sendMessage = async function (matchId) {
    const input = document.getElementById('message-input');
    const text = input.value.trim();
    if (!text) return;

    const sendBtn = document.getElementById('send-btn');
    sendBtn.disabled = true;

    try {
        const response = await fetch('/api/chat/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId, message: text })
        });
        const result = await response.json();

        if (result.success) {
            input.value = '';
            // startChatを呼び直すのではなく、メッセージエリアだけを更新する
            const res = await fetch(`/api/chat/history?match_id=${matchId}`);
            const newMsgs = await res.json();
            document.getElementById('chat-messages').innerHTML = renderMessages(newMsgs);
            scrollChatToBottom();
        }
    } catch (error) {
        console.error('Send Error:', error);
        alert('送信に失敗しました');
    } finally {
        sendBtn.disabled = false;
    }
}

function scrollChatToBottom() {
    const el = document.getElementById('chat-messages');
    if (el) el.scrollTop = el.scrollHeight;
}
