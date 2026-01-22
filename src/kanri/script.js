// ページ読み込み完了時に実行
window.addEventListener('DOMContentLoaded', async () => {
    await loadDepartments();
});

async function loadDepartments() {
    const deptSelect = document.getElementById('department');
    
    // api_main.php に送るリクエストデータ
    const requestData = {
        type: 'In',
        data: 'kamei'
    };

    try {
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();

        if (result.status) {
            // セレクトボックスの初期化
            deptSelect.innerHTML = '<option value="">選択してください</option>';
            
            // 取得したデータから選択肢を生成
            result.kamei.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.kamei_id;
                option.textContent = dept.kamei_mei;
                deptSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('科名の取得に失敗しました:', error);
        deptSelect.innerHTML = '<option value="">エラーが発生しました</option>';
    }
}

async function updateNumbers() {
    const now = new Date();
    const yearShort = now.getFullYear() % 100;
    const month = now.getMonth() + 1; // getMonth()は0-11なので、現在の月を正しく判定
    const deptId = document.getElementById('department').value;
    const grade = document.getElementById('grade').value;
    const numberSelect = document.getElementById('number');
    const resultDiv = document.getElementById('name-result'); // 名前表示もリセット用

    // 科名か学年が選ばれていない場合は、番号を初期化して終了
    if (!deptId || !grade) {
        numberSelect.innerHTML = '<option value="">選択してください</option>';
        resultDiv.innerText = "名前を表示"; // 科名や学年を変えたら名前表示も消す
        return;
    }

    // ID計算ロジック
    let id = 0;
    if (month >= 4) { // 4月以降（年度内）
        // 1年生なら今年、2年生なら去年入学
        id = ((yearShort - (grade - 1)) * 1000) + (Number(deptId) * 100);
    } else { // 1月〜3月
        // 1年生なら去年、2年生なら一昨年入学
        id = ((yearShort - grade) * 1000) + (Number(deptId) * 100);
    }

    console.log("生成されたベースID:", id);

    const requestData = {
        type: 'In',
        data: 'user_id',
        user_id_min: id
    };

    try {
        numberSelect.innerHTML = '<option value="">読み込み中...</option>';
        
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();
        numberSelect.innerHTML = '<option value="">選択してください</option>';

        if (result.status && result.numbers && result.numbers.length > 0) {
            result.numbers.forEach(num => {
                const option = document.createElement('option');
                option.value = num;
                option.textContent = num;
                numberSelect.appendChild(option);
            });
        } else {
            numberSelect.innerHTML = '<option value="">該当なし</option>';
        }
    } catch (error) {
        console.error('通信エラー:', error);
        numberSelect.innerHTML = '<option value="">エラー</option>';
    }
}

async function searchStudent() {
    const deptId = document.getElementById('department').value;
    const grade = document.getElementById('grade').value;
    const number = document.getElementById('number').value;
    const resultDiv = document.getElementById('name-result');

    // 番号が空（選択してください）の場合は表示をリセットして終了
    if (!number) {
        resultDiv.innerText = "名前を表示";
        resultDiv.style.color = "#666";
        return;
    }

    // すべての要素が揃っているかチェック
    if (!deptId || !grade) {
        alert("科名と学年を先に選択してください");
        return;
    }

    // IDの計算ロジック（updateNumbersと同じロジックにする）
    const now = new Date();
    const yearShort = now.getFullYear() % 100;
    const month = now.getMonth() + 1;
    let baseId = 0;

    if (month >= 4) {
        baseId = ((yearShort - (grade - 1)) * 1000) + (Number(deptId) * 100);
    } else {
        baseId = ((yearShort - grade) * 1000) + (Number(deptId) * 100);
    }
    
    // 最終的なユーザーID (例: 24101)
    const userId = baseId + Number(number); 

    try {
        resultDiv.innerText = "読み込み中...";
        
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type: 'In',
                data: 'user_name',
                user_id: userId
            })
        });

        const result = await response.json();

        if (result.status) {
            resultDiv.innerText = result.username; // 名前を表示
            resultDiv.style.color = "#000";
        } else {
            resultDiv.innerText = "学生が見つかりません";
            resultDiv.style.color = "red";
        }
    } catch (error) {
        console.error("名前の取得に失敗:", error);
        resultDiv.innerText = "通信エラー";
    }
}