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
    const month = now.getMonth();
    const deptId = document.getElementById('department').value;
    const grade = document.getElementById('grade').value;
    const numberSelect = document.getElementById('number');
    
    let id = 0; 
    
    if (!deptId || !grade) {
        numberSelect.innerHTML = '<option value="">選択してください</option>';
        return;
    } else {

        if (month > 3) {
            id = (yearShort * 1000) + (Number(deptId) * 100);
        } else {
            id = ((yearShort - grade) * 1000) + (Number(deptId) * 100);
        }
        
    }

    // 確認のためにコンソールに出す
    console.log("生成されたベースID:", id);

    // JSONデータを作成
    const requestData = {
        type: 'In',
        data: 'user_id',
        user_id_min: id
    };

    try {
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData) // JSON形式で送信
        });

        const result = await response.json();

        numberSelect.innerHTML = '<option value="">選択してください</option>';

        if (result.status && result.numbers.length > 0) {
            result.numbers.forEach(num => {
                const option = document.createElement('option');
                option.value = num;
                option.textContent = num;
                numberSelect.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.textContent = "該当なし";
            numberSelect.appendChild(option);
        }
    } catch (error) {
        console.error('通信エラー:', error);
    }
}

async function searchStudent() {
    const now = new Date();
    const yearShort = now.getFullYear() % 100;
    const month = now.getMonth();
    const deptId = document.getElementById('department').value;
    const grade = document.getElementById('grade').value;
    const number = document.getElementById('number').value;
    const resultDiv = document.getElementById('name-result');

    if (!deptId || !grade || !number) {
        alert("すべての項目を選択してください");
        return;
    }

    let baseId = 0; 
    
    if (!deptId || !grade) {
        numberSelect.innerHTML = '<option value="">選択してください</option>';
        return;
    } else {

        if (month > 3) {
            baseId = (yearShort * 1000) + (Number(deptId) * 100);
        } else {
            baseId = ((yearShort - grade) * 1000) + (Number(deptId) * 100);
        }
        
    }
    
    const userId = baseId + Number(number); 

    console.log("検索するユーザーID:", userId);

    try {
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
    }
}