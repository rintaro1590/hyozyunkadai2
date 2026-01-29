window.addEventListener('DOMContentLoaded', async () => {
    await loadDepartments();
});

/**
 * 共通1：年度・学年・科名からベースID（学籍番号の上3〜4桁）を計算する
 */
function getBaseId() {
    const deptId = document.getElementById('department').value;
    const grade = document.getElementById('grade').value;
    
    if (!deptId || !grade) return null;

    const now = new Date();
    const yearShort = now.getFullYear() % 100;
    const month = now.getMonth() + 1;

    let baseId = (month >= 4) ? ((yearShort - (grade - 1)) * 1000) : ((yearShort - grade) * 1000);
    return baseId + (Number(deptId) * 100);
}

/**
 * 共通2：ベースIDに選択された番号を足してフルID（学籍番号）を計算する
 */
function calculateUserId() {
    const baseId = getBaseId();
    const number = document.getElementById('number').value;
    
    if (baseId === null || !number) return null;
    return baseId + Number(number);
}

// 1. 科目一覧の読み込み
async function loadDepartments() {
    const deptSelect = document.getElementById('department');
    try {
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'In', data: 'kamei' })
        });
        const result = await response.json();
        // status -> response に変更
        if (result.response) {
            deptSelect.innerHTML = '<option value="">選択してください</option>';
            result.kamei.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.kamei_id;
                option.textContent = dept.kamei_mei;
                deptSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('科名取得失敗:', error);
    }
}

// 2. 番号リストをDBから取得する
async function updateNumbers() {
    const numberSelect = document.getElementById('number');
    const resultDiv = document.getElementById('name-result');
    const baseId = getBaseId();

    numberSelect.innerHTML = '<option value="">選択してください</option>';
    resultDiv.innerText = "名前を表示";
    resultDiv.style.color = "#666";

    if (baseId === null) return;

    numberSelect.innerHTML = '<option value="">読み込み中...</option>';

    try {
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                type: 'In', 
                data: 'user_id', 
                user_id_min: baseId 
            })
        });

        const result = await response.json();

        // status -> response に変更
        if (result.response && result.numbers) {
            numberSelect.innerHTML = '<option value="">選択してください</option>';
            result.numbers.forEach(num => {
                const option = document.createElement('option');
                option.value = num;
                option.textContent = num;
                numberSelect.appendChild(option);
            });
        } else {
            numberSelect.innerHTML = '<option value="">学生が見つかりません</option>';
        }
    } catch (error) {
        console.error('番号取得失敗:', error);
    }
}

// 4. 名前表示
async function showNameOnly() {
    const userId = calculateUserId();
    const resultDiv = document.getElementById('name-result');
    if (!userId) return;

    try {
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'In', data: 'user_name', user_id: userId })
        });
        const result = await response.json();
        // status -> response に変更
        if (result.response) {
            resultDiv.innerText = result.username;
            resultDiv.style.color = "#000";
        } else {
            resultDiv.innerText = "学生が見つかりません";
            resultDiv.style.color = "red";
        }
    } catch (e) { console.error(e); }
}

// 5. 検索ボタンクリック (POST遷移)
async function searchAndTransition() {
    const userId = calculateUserId();
    const resultDiv = document.getElementById('name-result');
    const userName = resultDiv.innerText;

    const invalidNames = ["名前を表示", "学生が見つかりません", "学生を正しく選択してください", "読み込み中..."];
    
    if (!userId || invalidNames.includes(userName)) {
        resultDiv.innerText = "学生を正しく選択してください";
        resultDiv.style.color = "red";
        return;
    }

    document.getElementById('post-user-id').value = userId;
    document.getElementById('post-user-name').value = userName;
    document.getElementById('transition-form').submit();
}