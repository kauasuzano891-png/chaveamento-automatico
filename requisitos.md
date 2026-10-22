# chaveamento Automático

## Objetivos
crie um site que faça um chaveamento automatico esportivos, onde o administrador vai ter acesso as chaves posteriormente, e poder acrescentar o placar das partidas. use o site como referencia. https://bracketmaker.app/pt/
Ajudar na organização, deixar o campeonato mais justo, ou seja, sem a manipulação do placar ou dos jogos.

### Stack Tecnlógico
- Backend: PHP estruturado com sessões nativas
- Banco de dados: MySQL (PDO para segurança)
- Frontend: HTML5, PHP, CSS, Tailwind CSS

#### Regras de negócio (CORE)
Tratar senhas de usuários com hash bcript
O sistema deve ter uma página de históricos e manter sempre os logs de qualquer alteração feita por 
qualquer usuário, para auditorias futuras.
- inserir informações
- validar informações
- gerar chaveamento
- exibir resultado
- permitir ajustes
- finalizar
  Sistema digital para organizar competições, define confrontos automáticamente, organiza a ordem das partidas, indica os classificados para a próxima fase, mostra o caminho até a final, registra o resultado e posições.
 


##### Regras Globais
- Use sempre PDO para conexão e queries no MySQL para evitar SQL Injections
- Mantenha o código limpo e comente apenas logicas complexas.
- Separe os arquivos de forma lógica: um arquivo para conexão com a base (bd.php) e scripts de backend isolados e views em HTML5/PHP, nunca faça o sistema como um monolito, deixe sempre separados todas as regras para facilitar os futuros upgrades.
 - Estilize as telas em Tailwind de forma responsiva priorizando o MobileFrist.
 - Retorne sempre as mensagens de erros de forma claras na interface para o usuário (TOAST)
 - sempre trate as mensagens de caixa de mensagens nativas do navegador em um MODAL

