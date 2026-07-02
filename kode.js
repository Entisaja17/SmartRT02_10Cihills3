async function handleLogin() {
    const user = document.getElementById('loginUsername').value;
    const pass = document.getElementById('loginPassword').value;

    try {
        // PASTIKAN URL-nya mengarah ke file PHP Anda!
        const response = await fetch(GAS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                action: 'login', 
                payload: { username: user, password: pass } 
            })
        });

        const textResponse = await response.text();

        try {
            const data = JSON.parse(textResponse);
            
            if(data.status === 'success') {
                console.log("Login sukses:", data);
                alert("Login Berhasil!");
                // Tambahkan kode untuk pindah ke dashboard di sini
            } else {
                alert("Gagal: " + data.message);
            }

        } catch (jsonError) {
            console.error("Bukan JSON:", textResponse);
            alert("Error server! Cek console browser.");
        }

    } catch (networkError) {
        console.error("Koneksi gagal:", networkError);
    }
}