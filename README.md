# 🏆 Chaveamento Automático - Sistema Esportivo Pro

Sistema web completo para organização, geração de chaveamento automático e homologação de competições esportivas escolares e comunitárias (interclasses, copas e ligas).

Desenvolvido rigorosamente em conformidade com as especificações descritas em [`requisitos.md`](requisitos.md) e inspirado na interface e usabilidade do [BracketMaker](https://bracketmaker.app/pt/).

---

## 🚀 Tecnologias Utilizadas

- **Backend:** PHP 8+ estruturado com sessões nativas e arquitetura desacoplada (não-monolito).
- **Banco de Dados:** MySQL com driver seguro **PDO** e Prepared Statements contra SQL Injection.
  - *Fallback transparente para SQLite:* Permite executar e testar o projeto de forma instantânea sem necessidade de inicializar o MySQL.
- **Frontend:** HTML5, CSS3, **Tailwind CSS** (design responsivo Mobile-First) e FontAwesome 6.
- **Segurança:** Senhas criptografadas com hash **Bcrypt** (`PASSWORD_BCRYPT`).
- **Auditoria:** Módulo completo de logs com rastreabilidade detalhada de todas as alterações feitas no sistema (placar antes x depois, usuário responsável, IP e data/hora).

---

## ⚡ Funcionalidades Principais

1. **Inserção & Validação Inteligente:**
   - Cadastro ágil do torneio com seleção de modalidade (Futsal, Futebol, Vôlei, Basquete, Handebol, Queimada, E-Sports, etc.).
   - Inserção de equipes linha por linha ou via exemplos rápidos.
   - Sorteio aleatório de confrontos para neutralidade e ausência de manipulações.
   - Cálculo automático de potências de 2 e distribuição equilibrada de folgas (**BYEs**).

2. **Visualização do Chaveamento (Estilo BracketMaker):**
   - Árvore gráfica e responsiva com scroll suave das fases (Oitavas, Quartas, Semifinais e Grande Final).
   - Suporte à disputa oficial de **3º Lugar** (Medalha de Bronze).
   - Indicação visual dos classificados e caminho até o título.
   - Opção de impressão limpa da tabela e compartilhamento de link público.

3. **Homologação de Resultados (Ajustes):**
   - Modal exclusivo em Tailwind para lançamento de placares (sem alertas nativos do navegador).
   - Validação de desempate por **pênaltis / prorrogação** em caso de empate eliminatório.
   - **Avanço automático:** Ao salvar o resultado, o vencedor é promovido instantaneamente para o slot correto da próxima fase!

4. **Transparência & Auditoria:**
   - Página dedicada (`auditoria.php`) com histórico de todos os eventos.
   - Exportação de relatórios em formato **CSV** compatível com Excel.
   - Proteção de integridade contra fraudes esportivas e manipulação de resultados.

5. **Feedback Visual Moderno:**
   - Notificações dinâmicas **TOAST** (sucesso, erro, aviso, info) que somem após 4 segundos.
   - Caixas de confirmação em **MODAL** elegante.

---

## 📁 Estrutura de Arquivos

```
chaveamento/
├── bd.php                  # Conexão central PDO, inicialização de tabelas e funções de log
├── database.sql            # Script SQL oficial para importação no MySQL
├── index.php               # Dashboard com indicadores, torneios ativos e pódios
├── novo_torneio.php        # Formulário e gerador automático de chaves
├── torneio.php             # Visualização da árvore estilo BracketMaker e súmulas
├── historico.php           # Histórico de campeonatos com filtros e busca
├── auditoria.php           # Painel de auditoria e logs contra manipulação
├── login.php               # Autenticação de organizadores
├── cadastro.php            # Criação de contas de organizadores (com Bcrypt)
├── actions/
│   ├── auth.php            # Lógica de login, cadastro e logout
│   ├── torneios.php        # Algoritmo de chaveamento, finalização e exclusão
│   ├── partidas.php        # Lançamento de placares e avanço de equipes
│   └── logs.php            # Exportação de relatórios de auditoria
├── includes/
│   ├── header.php          # Cabeçalho global, Tailwind CDN e metatags
│   ├── navbar.php          # Menu de navegação responsivo (Mobile & Desktop)
│   ├── footer.php          # Rodapé global e scripts
│   ├── toast.php           # Componente de notificações Toast
│   └── modal.php           # Modais de confirmação, alerta e súmula
├── assets/
│   ├── css/custom.css      # Estilização de conectores do bracket e impressão
│   └── js/app.js           # Gerenciamento de modais, toasts e formulários
└── requisitos.md           # Requisitos originais do projeto
```

---

## 🛠️ Como Executar

### Opção 1: Servidor Embutido do PHP (Rápido e Direto)
Abra o terminal na pasta do projeto e execute:
```bash
php -S localhost:8000
```
Acesse no seu navegador: **`http://localhost:8000`**

### Opção 2: XAMPP / WampServer / Laragon
1. Copie a pasta do projeto para o diretório `htdocs` (ou `www`).
2. Abra o **phpMyAdmin** e importe o arquivo `database.sql` (ou simplesmente abra a aplicação, pois o `bd.php` criará o banco automaticamente caso você tenha o MySQL ativo).
3. Acesse `http://localhost/chaveamento`.

---

## 🔐 Acesso Padrão de Demonstração

Para testar o lançamento de placares e criação de torneios:
- **E-mail:** `admin@etec.sp.gov.br`
- **Senha:** `admin123`
*(Você também pode cadastrar novos organizadores pela tela de cadastro)*

---

## 📄 Licença
Distribuído sob a licença [MIT](LICENSE). Desenvolvido para ETEC de Esportes.
