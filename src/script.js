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

    // api_main.php の仕様に合わせてJSONデータを作成
    const requestData = {
        type: 'In',
        user_id: id
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

        if (result.status === "success" && result.numbers.length > 0) {
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
    const deptId = document.getElementById('department').value;
    const grade = document.getElementById('grade').value;
    const number = document.getElementById('number').value;
    const resultDiv = document.getElementById('name-result');

    if (!deptId || !grade || !number) {
        alert("すべての項目を選択してください");
        return;
    }

    // --- IDの組み立てロジック ---
    // 例: 26 (年) + 7 (科ID) + 00 (固定) の 26700 がベース
    // そこに 1 (番号) を足して 26701 を作る
    const now = new Date();
    const yearShort = now.getFullYear() % 100;
    const baseId = (yearShort * 1000) + (Number(deptId) * 100);
    const userId = baseId + Number(number); 

    console.log("検索するユーザーID:", userId);

    try {
        const response = await fetch('../api/api_main.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type: 'GetName',
                user_id: userId
            })
        });

        const result = await response.json();

        if (result.status === "success") {
            resultDiv.innerText = result.name; // 名前を表示
            resultDiv.style.color = "#000";
        } else {
            resultDiv.innerText = "学生が見つかりません";
            resultDiv.style.color = "red";
        }
    } catch (error) {
        console.error("名前の取得に失敗:", error);
    }
}