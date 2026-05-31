<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Despacho Integrado IoT</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f9; }
        h2 { color: #333; margin-bottom: 20px; }
        .container { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .col-cadastro, .col-monitoramento { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; background: #fff; font-size: 14px; }
        button { width: 100%; background: #28a745; color: white; border: none; padding: 12px; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #0056b3; color: white; }
        .status-pendente { color: #555; font-weight: normal; }
        .status-acionando { color: #ff9900; font-weight: bold; animation: piscar 1.5s infinite; }
        .status-confirmado { color: #28a745; font-weight: bold; }
        .status-falha { color: #dc3545; font-weight: bold; }
        @keyframes piscar {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
    </style>
</head>
<body>

    <h2>Painel de Despacho - Operação de Missão Crítica</h2>

    <div class="container">
        <div class="col-cadastro">
            <h3>Novo Registro de Emergência</h3>
            <div class="form-group">
                <label for="descricao">Descrição da Ocorrência</label>
                <textarea id="descricao" rows="4" placeholder="Ex: Incêndio estrutural em colégio comercial..."></textarea>
            </div>
            <div class="form-group">
                <label for="viatura">Prefixo da Viatura</label>
                <select id="viatura">
                    <option value="UR-10101">UR-10101</option>
                    <option value="UR-10103">UR-10103</option>
                    <option value="UR-10107">UR-10107</option>
                </select>
            </div>
            <button onclick="cadastrarEDespachar()" id="btn-enviar">Enviar e Acionar Base</button>
        </div>

        <div class="col-monitoramento">
            <h3>Monitoramento de Prontidão (Sincronizado via AJAX)</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Viatura</th>
                        <th>Status Atual</th>
                    </tr>
                </thead>
                <tbody id="tabela-ocorrencias">
                    <?php
                    if (isset($erroBanco) && $erroBanco !== null) {
                        echo "<tr><td colspan='4' style='color:#dc3545; font-weight:bold; background:#f8d7da; padding:15px;'>Erro: " . htmlspecialchars($erroBanco) . "</td></tr>";
                    } elseif (isset($stmt)) {
                        if ($stmt->rowCount() == 0) {
                            echo "<tr id='sem-dados'><td colspan='4' style='text-align:center; color:#777; font-style:italic;'>Nenhuma ocorrência registrada no banco de dados.</td></tr>";
                        }

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $classeStatus = 'status-pendente';
                            $textoStatus = 'Pendente';
                            
                            if ($row['status'] == 'acionado') { 
                                $classeStatus = 'status-acionando'; 
                                $textoStatus = 'Acionando...'; 
                            }
                            elseif ($row['status'] == 'confirmado_base') { 
                                $classeStatus = 'status-confirmado'; 
                                $textoStatus = 'Viatura Acionada'; 
                            }
                            elseif ($row['status'] == 'falha_timeout') { 
                                $classeStatus = 'status-falha'; 
                                $textoStatus = 'Erro: Acione via Rádio!'; 
                            }
                            
                            echo "<tr id='linha_{$row['id']}'>
                                    <td>{$row['id']}</td>
                                    <td>" . htmlspecialchars($row['descricao']) . "</td>
                                    <td>" . htmlspecialchars($row['viatura']) . "</td>
                                    <td id='status_{$row['id']}' class='{$classeStatus}'>{$textoStatus}</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function carregarStatusTempoRealAJAX() {
        fetch('verificar_status.php')
        .then(response => response.json())
        .then(resultado => {
            if (resultado.sucesso) {
                resultado.dados.forEach(item => {
                    const celulaStatus = document.getElementById('status_' + item.id);
                    
                    if (celulaStatus) {
                        if (item.status === 'acionado' && celulaStatus.innerText !== 'Acionando...') {
                            celulaStatus.innerText = 'Acionando...';
                            celulaStatus.className = 'status-acionando';
                        } 
                        else if (item.status === 'confirmado_base' && celulaStatus.innerText !== 'Viatura Acionada') {
                            celulaStatus.innerText = 'Viatura Acionada';
                            celulaStatus.className = 'status-confirmado';
                        }
                        else if (item.status === 'falha_timeout' && celulaStatus.innerText !== 'Timeout: Acione via Rádio!') {
                            celulaStatus.innerText = 'Erro: Acione via Rádio!';
                            celulaStatus.className = 'status-falha';
                        }
                        else if (item.status === 'Pendente' && celulaStatus.innerText !== 'Pendente') {
                            celulaStatus.innerText = 'Pendente';
                            celulaStatus.className = 'status-pendente';
                        }
                    }
                });
            }
        })
        .catch(erro => console.error('Falha na sincronização assíncrona:', erro));
    }

    setInterval(carregarStatusTempoRealAJAX, 2000);

    function cadastrarEDespachar() {
        const desc = document.getElementById('descricao').value;
        const viat = document.getElementById('viatura').value;
        const btn = document.getElementById('btn-enviar');
        
        if(!desc || !viat) {
            alert('Preencha a descrição da ocorrência antes de enviar.');
            return;
        }

        btn.disabled = true;

        fetch('cadastrar_e_despachar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `descricao=${encodeURIComponent(desc)}&viatura=${encodeURIComponent(viat)}`
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            if(data.sucesso) {
                const tabela = document.getElementById('tabela-ocorrencias');
                const linhaVazia = document.getElementById('sem-dados');
                if (linhaVazia) linhaVazia.remove();

                const novaLinha = `
                    <tr id="linha_${data.id}">
                        <td>${data.id}</td>
                        <td>${escapeHtml(desc)}</td>
                        <td>${escapeHtml(viat)}</td>
                        <td id="status_${data.id}" class="status-acionando">Acionando...</td>
                    </tr>
                `;
                tabela.insertAdjacentHTML('afterbegin', novaLinha);
                
                document.getElementById('descricao').value = '';
                document.getElementById('viatura').selectedIndex = 0;
            } else {
                alert('Erro no processamento: ' + data.mensagem);
            }
        })
        .catch(err => {
            btn.disabled = false;
            alert('Falha de rede: ' + err);
        });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
    </script>
</body>
</html>
