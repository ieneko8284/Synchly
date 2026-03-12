// public/js/app.js

// 1. ページが読み込まれたら実行
document.addEventListener('DOMContentLoaded', () => {
    console.log('Synchly App Ready!');
    loadUsers(); // ユーザー一覧を読み込む
});

// 2. ユーザー一覧をAPIから取得して表示する関数
// public/js/app.js (loadUsers関数の中をアップグレード)

async function loadUsers() {
    const contentArea = document.getElementById('app-content');
    try {
        const response = await fetch('/api/users');
        const users = await response.json();
        contentArea.innerHTML = '';

        users.forEach(user => {
            const card = document.createElement('div');
            card.className = 'user-card';
            card.innerHTML = `
                <div class="user-info">
                    <h3>${user.username}</h3>
                    <p>${user.bio}</p>
                </div>
                <button class="like-btn" data-id="${user.id}">♥</button>
            `;
            contentArea.appendChild(card);
        });

        // いいねボタンにイベントを設定
        initLikeEvents();

    } catch (error) {
        console.error('Error:', error);
    }
}

function initLikeEvents() {
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('is-active')) return;

            // 1. アクティブ状態にする（鼓動アニメーション開始）
            this.classList.add('is-active');

            // 2. 波紋エフェクト（Ripple）を生成
            const ripple = document.createElement('div');
            ripple.className = 'ripple';
            this.appendChild(ripple);

            // 3. 一定時間後に波紋要素を消す
            setTimeout(() => ripple.remove(), 600);

            // 4. 将来的にここでAPI（/api/like）を叩いてDB保存する
            const targetId = this.dataset.id;
            console.log(`User ${targetId} にいいねしました！`);
        });
    });
}

// 3. ナビゲーションの切り替え（簡易版）
document.querySelectorAll('.nav-item').forEach(button => {
    button.addEventListener('click', (e) => {
        // 全ボタンのactiveを外して、クリックされたやつにつける
        document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
        e.currentTarget.classList.add('active');

        const page = e.currentTarget.dataset.page;
        console.log(`${page} ページへ切り替え（未実装）`);
        
        // ここにページごとの読み込み処理を今後追加していくよ！
    });
});