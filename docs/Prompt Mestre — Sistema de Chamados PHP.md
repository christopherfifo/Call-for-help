# SISTEMA DE CHAMADOS — PROMPT MESTRE

Você é um engenheiro de software sênior responsável por desenvolver um sistema web completo de gerenciamento de chamados de TI.

O sistema deverá ser desenvolvido em:

- PHP
- MySQL
- Bootstrap
- HTML5
- CSS3
- JavaScript
- PDO para acesso ao banco de dados

O sistema deve ser responsivo, seguro, organizado e preparado para manutenção futura.

Não utilize frameworks PHP como Laravel. O objetivo é desenvolver o sistema utilizando PHP estruturado/organizado, separando responsabilidades adequadamente.

---

# 1. OBJETIVO DO SISTEMA

Criar um sistema interno de chamados de TI onde usuários possam abrir e acompanhar solicitações, técnicos possam realizar os atendimentos e administradores possam gerenciar usuários, técnicos, chamados e relatórios.

O sistema terá quatro níveis de acesso:

1. Usuário
2. Técnico
3. Administrador
4. DEV / Superadministrador

Cada perfil deve possuir permissões próprias.

IMPORTANTE:

Nunca confie apenas na interface para controlar permissões.

Toda autorização deve ser validada no backend.

Um usuário não pode acessar dados de outro usuário simplesmente alterando IDs na URL.

Utilize sessões PHP, controle de acesso por perfil, prepared statements com PDO, password_hash/password_verify e proteção contra SQL Injection, XSS e CSRF.

---

# 2. REGRAS FUNDAMENTAIS

Estas regras são obrigatórias:

- Usuários nunca são excluídos fisicamente.
- Usuários devem ser apenas desativados.
- Chamados nunca devem ser apagados.
- Todas as ações importantes devem gerar histórico.
- Toda alteração importante deve registrar:
  - quem realizou;
  - data;
  - hora;
  - ação realizada;
  - informação anterior, quando aplicável;
  - nova informação, quando aplicável;
  - justificativa, quando obrigatória.
- Senhas nunca podem ser armazenadas em texto puro.
- O usuário só pode acessar seus próprios chamados.
- Técnicos possuem acesso aos chamados necessários para o atendimento.
- Administradores possuem acesso global.
- Apenas DEV/Superadministrador pode gerenciar outros administradores.
- Não permitir elevação de privilégio pelo próprio usuário.

---

# 3. PERFIS

## 3.1 USUÁRIO

O usuário pode:

- Fazer login.
- Alterar sua senha.
- Visualizar seu perfil.
- Abrir chamados.
- Visualizar seus próprios chamados.
- Acompanhar o andamento.
- Adicionar informações posteriormente.
- Cancelar seus próprios chamados.
- Solicitar relatório de um chamado.

O usuário NÃO pode:

- Visualizar chamados de outras pessoas.
- Alterar técnico.
- Alterar status livremente.
- Alterar urgência depois da abertura.
- Acessar área administrativa.
- Acessar área técnica.
- Criar usuários.

---

# 4. TÉCNICO

O técnico pode:

- Fazer login.
- Visualizar dashboard.
- Visualizar a fila de chamados.
- Visualizar chamados necessários para atendimento.
- Assumir chamados.
- Definir prazo.
- Definir prazo indeterminado.
- Informar justificativa do prazo.
- Alterar status permitido.
- Pausar chamado.
- Retomar chamado.
- Repassar chamado.
- Devolver chamado para a fila.
- Informar justificativa ao repassar.
- Finalizar chamado.
- Informar relatório final.
- Solicitar relatório ao administrador.
- Visualizar histórico do chamado.

O técnico NÃO pode:

- Cancelar chamados.
- Criar administradores.
- Alterar usuários.
- Alterar cargos administrativos.
- Alterar a urgência diretamente.
- Excluir chamados.

---

# 5. ADMINISTRADOR

O administrador pode:

- Visualizar dashboard geral.
- Cadastrar usuários.
- Cadastrar técnicos.
- Abrir chamados em nome de usuários.
- Visualizar todos os chamados.
- Filtrar chamados.
- Pesquisar chamados.
- Alterar técnico responsável.
- Alterar status quando permitido pelas regras.
- Cancelar chamados.
- Reabrir chamados.
- Alterar urgência.
- Gerenciar usuários.
- Desativar usuários.
- Reativar usuários.
- Resetar senha.
- Alterar cargo de usuários.
- Visualizar solicitações de relatório.
- Gerar relatórios PDF.
- Visualizar histórico completo.

O administrador NÃO pode:

- Criar outro administrador.
- Alterar seu próprio nível de privilégio.
- Criar/gerenciar contas DEV.

Essas funções pertencem exclusivamente ao DEV/Superadministrador.

---

# 6. DEV / SUPERADMINISTRADOR

O DEV/Superadministrador possui todas as permissões do administrador e também:

- Cadastrar administradores.
- Desativar administradores.
- Reativar administradores.
- Resetar senha de administradores.
- Alterar nível administrativo.
- Visualizar logs administrativos.
- Visualizar auditoria do sistema.
- Gerenciar configurações globais.

A criação de administradores deve ser realizada exclusivamente por esse perfil.

---

# 7. LOGIN

Criar sistema de autenticação.

Cadastro inicial:

- Nome
- E-mail
- Matrícula
- Senha provisória

A matrícula será utilizada como senha provisória inicialmente.

No primeiro acesso:

- obrigar usuário a criar nova senha;
- impedir acesso ao sistema até trocar a senha.

Utilizar:

- password_hash()
- password_verify()

Nunca salvar senha em texto puro.

Criar logout seguro.

Criar proteção de sessão.

---

# 8. CHAMADOS

Cada chamado deverá possuir:

- ID
- Número público do chamado
- Usuário solicitante
- Técnico responsável
- Tipo/categoria
- Assunto
- Descrição
- Urgência
- Justificativa da urgência
- Status
- Prazo
- Indicador de prazo indeterminado
- Justificativa do prazo
- Relatório final
- Data de abertura
- Data da última atualização
- Data de finalização
- Data de cancelamento
- Usuário que cancelou

---

# 9. CATEGORIAS

Criar categorias configuráveis.

Categorias iniciais:

- Hardware
- Software
- Sistema
- Rede
- Impressora
- Acesso
- E-mail
- Telefonia
- Equipamento
- Outro

O administrador poderá futuramente gerenciar essas categorias.

---

# 10. ABERTURA DE CHAMADO

O usuário deve informar:

- Assunto/motivo
- Categoria
- Descrição
- Urgência

Urgência:

- Alta
- Moderada
- Leve

A justificativa da urgência é obrigatória.

Exemplo:

Urgência: Alta

Justificativa:

"Computador utilizado diretamente no atendimento ao público está indisponível."

Ao criar o chamado:

Status inicial:

ABERTO

Registrar no histórico:

"Chamado aberto por [usuário]."

---

# 11. STATUS

Os status principais serão:

- ABERTO
- EM ANDAMENTO
- PAUSADO
- FINALIZADO
- CANCELADO

Não tratar REPASSADO e REABERTO como estados permanentes.

Eles devem ser eventos/tags.

Exemplo:

Status:

ABERTO

Tag:

REPASSADO

Outro exemplo:

Status:

EM ANDAMENTO

Tag:

REABERTO

Isso evita inconsistência no fluxo.

---

# 12. FLUXO DO CHAMADO

Fluxo normal:

ABERTO
↓
EM ANDAMENTO
↓
FINALIZADO

Também pode ocorrer:

EM ANDAMENTO
↓
PAUSADO
↓
EM ANDAMENTO

Ou:

EM ANDAMENTO
↓
ABERTO + TAG REPASSADO

Ou:

FINALIZADO
↓
REABERTO
↓
EM ANDAMENTO

Ou:

ABERTO / EM ANDAMENTO
↓
CANCELADO

---

# 13. ASSUMIR CHAMADO

Quando o técnico assumir um chamado:

Status:

ABERTO → EM ANDAMENTO

O técnico deverá obrigatoriamente informar:

### Prazo

Pode escolher:

- Data e hora definida
- Prazo indeterminado

### Justificativa

Obrigatória nos dois casos.

Exemplo:

Prazo:

06/09/2026 às 17:00

Justificativa:

"Necessário aguardar substituição do equipamento."

Ou:

Prazo:

Indeterminado

Justificativa:

"Aguardando retorno do fornecedor."

Registrar tudo no histórico.

---

# 14. PAUSAR CHAMADO

O técnico pode pausar.

Para pausar, deve informar obrigatoriamente:

- motivo da pausa

Exemplo:

"Aguardando peça para substituição."

Registrar:

- técnico;
- data;
- hora;
- status anterior;
- novo status;
- motivo.

---

# 15. RETOMAR CHAMADO

Chamado:

PAUSADO → EM ANDAMENTO

Registrar a ação no histórico.

---

# 16. REPASSAR CHAMADO

O técnico pode:

### Opção A — Repassar para outro técnico

Selecionar outro técnico.

O chamado passa para o novo responsável.

### Opção B — Devolver para a fila

O chamado volta para:

ABERTO

e recebe a tag:

REPASSADO

Em ambos os casos:

Justificativa obrigatória.

Exemplo:

"Chamado relacionado à configuração de rede, área que não está sob minha responsabilidade."

Registrar no histórico.

---

# 17. FINALIZAR CHAMADO

O técnico só poderá finalizar se preencher:

### Relatório final

Exemplo:

"Realizada reinstalação do driver de rede e configuração do adaptador. Após os testes, o equipamento voltou a acessar a rede normalmente."

Fluxo:

EM ANDAMENTO → FINALIZADO

Registrar:

- técnico;
- data;
- hora;
- relatório.

---

# 18. CANCELAMENTO

Somente:

- dono do chamado;
- administrador

podem cancelar.

O cancelamento exige justificativa obrigatória.

Registrar:

- quem cancelou;
- perfil;
- data;
- hora;
- justificativa.

Exemplo:

"Problema solucionado pelo próprio usuário."

Nunca apagar o chamado.

---

# 19. REABERTURA

Somente administrador pode reabrir chamado finalizado.

Exigir justificativa.

Exemplo:

"Usuário informou que o problema voltou a ocorrer."

Fluxo:

FINALIZADO
↓
REABERTO
↓
EM ANDAMENTO

Adicionar tag:

REABERTO

Registrar tudo no histórico.

---

# 20. ALTERAÇÃO DA URGÊNCIA

O administrador pode alterar a urgência.

Porém, toda alteração exige justificativa.

Registrar:

- urgência anterior;
- nova urgência;
- administrador;
- data;
- hora;
- justificativa.

Exemplo:

MODERADA → ALTA

Justificativa:

"Equipamento utilizado no atendimento ao público ficou indisponível."

---

# 21. ATUALIZAÇÕES DO USUÁRIO

Não permitir que o usuário simplesmente edite a descrição original.

Criar:

### Atualizações do solicitante

O usuário poderá adicionar informações posteriormente.

Exemplo:

"O problema também ocorre no computador da recepção."

Registrar:

- mensagem;
- usuário;
- data;
- hora.

A descrição original deve permanecer intacta.

---

# 22. HISTÓRICO / AUDITORIA

Todo chamado deverá possuir uma timeline.

Exemplo:

05/09/2026 10:15
Christopher abriu o chamado.

05/09/2026 10:20
João assumiu o chamado.

05/09/2026 10:21
Prazo definido para 06/09/2026 às 17:00.

05/09/2026 11:40
João adicionou uma atualização.

05/09/2026 14:20
Chamado pausado.

05/09/2026 16:00
Chamado retomado.

05/09/2026 17:10
Chamado finalizado.

O histórico não pode ser apagado pelo usuário comum.

---

# 23. SOLICITAÇÃO DE RELATÓRIO

Usuário e técnico podem solicitar o relatório de um chamado.

Criar botão:

"Solicitar relatório"

Ao solicitar:

- motivo obrigatório;
- observação opcional.

Criar status:

- Pendente
- Atendido
- Recusado, caso essa opção seja implementada

O administrador terá uma tela:

### Solicitações de relatório

Mostrar:

- chamado;
- solicitante;
- perfil;
- data;
- motivo;
- status.

O administrador poderá:

- visualizar;
- gerar relatório;
- baixar PDF;
- adicionar observação;
- marcar como atendido.

Registrar a solicitação no histórico.

---

# 24. RELATÓRIO PDF

Criar relatório profissional em PDF contendo:

- Identificação do chamado;
- número;
- solicitante;
- e-mail;
- categoria;
- assunto;
- descrição;
- urgência;
- justificativa da urgência;
- técnico;
- prazo;
- justificativa do prazo;
- data de abertura;
- data de finalização;
- relatório final;
- histórico completo;
- atualizações;
- cancelamentos;
- reaberturas;
- alterações de urgência;
- repasses.

O PDF deve possuir aparência profissional e adequada para impressão.

---

# 25. DASHBOARD DO USUÁRIO

Mostrar:

- Meus chamados abertos;
- Em andamento;
- Pausados;
- Finalizados;
- Cancelados.

Mostrar lista de chamados recentes.

---

# 26. DASHBOARD DO TÉCNICO

Mostrar:

- Chamados abertos;
- Meus chamados em andamento;
- Chamados pausados;
- Chamados próximos do prazo;
- Chamados atrasados;
- Chamados finalizados recentemente.

---

# 27. DASHBOARD DO ADMINISTRADOR

Mostrar cards:

- Abertos
- Em andamento
- Pausados
- Finalizados
- Cancelados
- Reabertos
- Próximos do prazo
- Prazo vencido

Mostrar chamados prioritários.

---

# 28. ORGANIZAÇÃO DOS CHAMADOS

Os chamados devem ser organizados por:

1. Urgência
2. Data de abertura

Alta prioridade primeiro.

Dentro da mesma urgência:

chamados mais antigos primeiro.

Criar filtros:

- Status
- Urgência
- Técnico
- Categoria
- Usuário
- Período
- Número do chamado

Criar campo de pesquisa.

Criar abas:

- Todos
- Abertos
- Em andamento
- Pausados
- Finalizados
- Cancelados

---

# 29. SLA / PRAZO

Criar indicação visual do prazo:

- Dentro do prazo
- Próximo do vencimento
- Prazo vencido

Exemplo:

🟢 Dentro do prazo

🟡 Próximo do vencimento

🔴 Prazo vencido

Não considerar prazo indeterminado como atrasado.

---

# 30. USUÁRIOS

Tela administrativa:

### Usuários

Colunas:

- Nome
- Matrícula
- E-mail
- Cargo
- Status
- Data de cadastro
- Ações

Ações:

- Editar
- Alterar cargo
- Resetar senha
- Desativar
- Reativar

Nunca excluir fisicamente.

---

# 31. ADMINISTRADORES

Criar uma tela exclusiva:

### Gerenciamento de administradores

Disponível apenas para DEV/Superadministrador.

Permitir:

- Cadastrar administrador;
- Editar;
- Desativar;
- Reativar;
- Resetar senha;
- Alterar nível administrativo.

Níveis:

- Administrador
- Superadministrador

Registrar alterações administrativas em log.

---

# 32. ANEXOS

Adicionar suporte a anexos nos chamados.

Usuários podem anexar:

- imagens;
- PDFs;
- documentos;
- arquivos relacionados ao problema.

Técnicos também podem adicionar arquivos.

Controlar:

- nome original;
- extensão;
- tamanho;
- usuário que enviou;
- data;
- chamado relacionado.

Validar extensões e tamanho dos arquivos.

Nunca confiar apenas na extensão enviada pelo navegador.

---

# 33. SEGURANÇA

Implementar obrigatoriamente:

- PDO + prepared statements;
- password_hash;
- password_verify;
- CSRF tokens;
- proteção contra XSS;
- validação de dados;
- escaping de saída;
- controle de sessão;
- controle de permissões no backend;
- proteção contra acesso direto a recursos;
- validação de upload;
- prevenção de SQL Injection;
- logout seguro.

Não colocar senha ou credenciais do banco diretamente espalhadas pelo código.

Utilizar arquivo de configuração apropriado.

---

# 34. BANCO DE DADOS

Criar banco MySQL normalizado.

Estrutura inicial sugerida:

usuarios

- id
- nome
- email
- matricula
- senha
- cargo
- ativo
- primeiro_acesso
- criado_em
- atualizado_em

chamados

- id
- numero
- usuario_id
- tecnico_id
- categoria_id
- assunto
- descricao
- status
- urgencia
- justificativa_urgencia
- prazo
- prazo_indeterminado
- justificativa_prazo
- relatorio_final
- criado_em
- atualizado_em
- finalizado_em
- cancelado_em
- cancelado_por

categorias

- id
- nome
- ativo
- criado_em

historico_chamados

- id
- chamado_id
- usuario_id
- acao
- status_anterior
- status_novo
- descricao
- criado_em

comentarios_chamados

- id
- chamado_id
- usuario_id
- comentario
- criado_em

solicitacoes_relatorio

- id
- chamado_id
- solicitante_id
- motivo
- observacao
- status
- atendido_por
- atendido_em
- criado_em

anexos

- id
- chamado_id
- usuario_id
- nome_original
- caminho
- tipo
- tamanho
- criado_em

logs_sistema

- id
- usuario_id
- acao
- descricao
- ip
- criado_em

Ajuste a estrutura caso seja necessário para manter normalização e integridade referencial.

Utilize foreign keys e índices adequados.

---

# 35. INTERFACE

Utilizar Bootstrap.

Interface:

- limpa;
- profissional;
- responsiva;
- adequada para desktop;
- adequada para tablet;
- adequada para celular.

Criar:

- sidebar;
- navbar;
- dashboard;
- cards;
- tabelas responsivas;
- badges de status;
- badges de urgência;
- modais;
- formulários;
- alertas;
- paginação.

Manter consistência visual em todas as telas.

---

# 36. ARQUITETURA

Não colocar todo o sistema em um único arquivo PHP.

Organizar o projeto de maneira profissional.

Exemplo:

/config
/includes
/auth
/admin
/tecnico
/usuario
/chamados
/assets
/uploads
/reports
/database

Utilizar funções/classes reutilizáveis quando fizer sentido.

Separar:

- conexão;
- autenticação;
- autorização;
- regras de negócio;
- banco;
- interface.

---

# 37. REGRAS PARA O DESENVOLVIMENTO

Antes de começar:

1. Analise completamente o projeto existente.
2. Verifique se já existem arquivos.
3. Verifique se já existe banco de dados.
4. Não apague código existente sem necessidade.
5. Não substitua funcionalidades funcionais sem motivo.
6. Identifique conflitos antes de modificar.
7. Planeje a arquitetura.
8. Crie o banco e migrations/scripts necessários.
9. Depois implemente por etapas.

Não tente criar tudo de uma única vez.

---

# 38. METODOLOGIA DE TASKS

Você deverá desenvolver o sistema através das tasks abaixo.

Mantenha um arquivo:

/TASKS.md

Esse arquivo deve conter todas as tasks e seus respectivos estados:

[ ] Pendente
[~] Em andamento
[x] Concluída

Após concluir uma task, atualize o TASKS.md.

Não marque uma task como concluída sem verificar se ela realmente funciona.

Quando uma task depender de outra, conclua primeiro a dependência.

---

# TASK 01 — ANÁLISE DO PROJETO

- [ ] Analisar estrutura existente.
- [ ] Identificar tecnologias.
- [ ] Identificar banco.
- [ ] Identificar arquivos existentes.
- [ ] Criar arquitetura planejada.
- [ ] Criar TASKS.md.

Não implementar funcionalidades ainda.

---

# TASK 02 — BANCO DE DADOS

- [ ] Criar estrutura MySQL.
- [ ] Criar tabelas.
- [ ] Criar foreign keys.
- [ ] Criar índices.
- [ ] Criar usuário inicial DEV/Superadmin.
- [ ] Criar categorias iniciais.
- [ ] Criar script SQL de instalação.
- [ ] Testar relacionamentos.

---

# TASK 03 — AUTENTICAÇÃO

- [ ] Login.
- [ ] Logout.
- [ ] Sessões.
- [ ] Password hash.
- [ ] Password verify.
- [ ] Primeiro acesso.
- [ ] Troca obrigatória de senha.
- [ ] Proteção das páginas.
- [ ] Controle de perfil.

---

# TASK 04 — ESTRUTURA DA INTERFACE

- [ ] Layout Bootstrap.
- [ ] Sidebar.
- [ ] Navbar.
- [ ] Responsividade.
- [ ] Componentes reutilizáveis.
- [ ] Alertas.
- [ ] Modais.
- [ ] Badges.
- [ ] Tabelas.
- [ ] Paginação.

---

# TASK 05 — USUÁRIOS

- [ ] Cadastro.
- [ ] Edição.
- [ ] Desativação.
- [ ] Reativação.
- [ ] Alteração de cargo.
- [ ] Reset de senha.
- [ ] Primeiro acesso.

---

# TASK 06 — ADMINISTRADORES

- [ ] Área DEV.
- [ ] Cadastro de administradores.
- [ ] Edição.
- [ ] Desativação.
- [ ] Reativação.
- [ ] Reset de senha.
- [ ] Controle de níveis.
- [ ] Logs administrativos.

---

# TASK 07 — CATEGORIAS

- [ ] CRUD de categorias.
- [ ] Ativação/desativação.
- [ ] Validação.

---

# TASK 08 — ABERTURA DE CHAMADOS

- [ ] Formulário.
- [ ] Categoria.
- [ ] Assunto.
- [ ] Descrição.
- [ ] Urgência.
- [ ] Justificativa.
- [ ] Número automático.
- [ ] Status inicial ABERTO.
- [ ] Histórico inicial.

---

# TASK 09 — VISUALIZAÇÃO DOS CHAMADOS

- [ ] Lista.
- [ ] Detalhes.
- [ ] Filtros.
- [ ] Pesquisa.
- [ ] Ordenação por urgência.
- [ ] Ordenação por antiguidade.
- [ ] Paginação.
- [ ] Badges.
- [ ] Controle de acesso.

---

# TASK 10 — FILA DO TÉCNICO

- [ ] Fila.
- [ ] Assumir chamado.
- [ ] Definir prazo.
- [ ] Prazo indeterminado.
- [ ] Justificativa.
- [ ] Histórico.

---

# TASK 11 — PAUSA E RETOMADA

- [ ] Pausar.
- [ ] Justificativa.
- [ ] Retomar.
- [ ] Histórico.

---

# TASK 12 — REPASSE

- [ ] Repassar para técnico.
- [ ] Devolver para fila.
- [ ] Tag REPASSADO.
- [ ] Justificativa.
- [ ] Histórico.

---

# TASK 13 — FINALIZAÇÃO

- [ ] Relatório final obrigatório.
- [ ] Finalização.
- [ ] Data/hora.
- [ ] Histórico.
- [ ] Bloqueio de finalização sem relatório.

---

# TASK 14 — CANCELAMENTO

- [ ] Cancelamento pelo usuário.
- [ ] Cancelamento pelo administrador.
- [ ] Justificativa obrigatória.
- [ ] Registro de quem cancelou.
- [ ] Histórico.
- [ ] Bloqueio para técnico.

---

# TASK 15 — REABERTURA

- [ ] Reabertura pelo administrador.
- [ ] Justificativa.
- [ ] Tag REABERTO.
- [ ] Histórico.
- [ ] Retorno para atendimento.

---

# TASK 16 — ALTERAÇÃO DE URGÊNCIA

- [ ] Alteração pelo administrador.
- [ ] Justificativa obrigatória.
- [ ] Histórico.
- [ ] Registro anterior/novo valor.

---

# TASK 17 — ATUALIZAÇÕES

- [ ] Usuário adicionar atualização.
- [ ] Técnico adicionar atualização.
- [ ] Histórico.
- [ ] Preservar descrição original.

---

# TASK 18 — ANEXOS

- [ ] Upload.
- [ ] Validação.
- [ ] Download.
- [ ] Controle de acesso.
- [ ] Histórico.

---

# TASK 19 — SOLICITAÇÃO DE RELATÓRIO

- [ ] Usuário solicitar.
- [ ] Técnico solicitar.
- [ ] Motivo obrigatório.
- [ ] Tela administrativa.
- [ ] Status da solicitação.
- [ ] Atendimento.
- [ ] Histórico.

---

# TASK 20 — RELATÓRIO PDF

- [ ] Layout profissional.
- [ ] Dados do chamado.
- [ ] Histórico.
- [ ] Atualizações.
- [ ] Relatório final.
- [ ] Dados de cancelamento.
- [ ] Dados de reabertura.
- [ ] Dados de repasse.
- [ ] Geração.
- [ ] Download.

---

# TASK 21 — DASHBOARDS

- [ ] Dashboard usuário.
- [ ] Dashboard técnico.
- [ ] Dashboard administrador.
- [ ] Dashboard DEV.
- [ ] Indicadores.
- [ ] Chamados prioritários.
- [ ] Chamados atrasados.

---

# TASK 22 — SLA

- [ ] Controle de prazo.
- [ ] Dentro do prazo.
- [ ] Próximo do vencimento.
- [ ] Vencido.
- [ ] Prazo indeterminado.

---

# TASK 23 — AUDITORIA

- [ ] Histórico completo.
- [ ] Logs do sistema.
- [ ] Logs administrativos.
- [ ] Registro de IP quando apropriado.
- [ ] Impossibilidade de alteração por usuários comuns.

---

# TASK 24 — SEGURANÇA

Revisar todo o sistema:

- [ ] SQL Injection.
- [ ] XSS.
- [ ] CSRF.
- [ ] Session hijacking.
- [ ] Controle de acesso.
- [ ] Upload.
- [ ] Senhas.
- [ ] Acesso direto às páginas.
- [ ] IDOR.
- [ ] Validação de entrada.

---

# TASK 25 — TESTES

Criar testes manuais/automatizados quando possível.

Testar:

- [ ] Login.
- [ ] Usuário.
- [ ] Técnico.
- [ ] Administrador.
- [ ] DEV.
- [ ] Chamado.
- [ ] Permissões.
- [ ] Cancelamento.
- [ ] Reabertura.
- [ ] Repasse.
- [ ] Finalização.
- [ ] Relatório.
- [ ] PDF.
- [ ] Responsividade.

Tentar acessar recursos com usuários sem permissão.

---

# TASK 26 — REVISÃO FINAL

- [ ] Revisar código.
- [ ] Remover código morto.
- [ ] Corrigir erros.
- [ ] Melhorar organização.
- [ ] Verificar segurança.
- [ ] Verificar responsividade.
- [ ] Verificar banco.
- [ ] Verificar integridade dos dados.
- [ ] Verificar todas as permissões.
- [ ] Atualizar documentação.

---

# COMPORTAMENTO OBRIGATÓRIO DA IA

Durante o desenvolvimento:

1. Não pule tasks.
2. Não implemente várias tasks gigantes simultaneamente.
3. Antes de cada task, analise as dependências.
4. Depois de cada task, teste.
5. Atualize TASKS.md.
6. Se encontrar um problema arquitetural, corrija antes de continuar.
7. Não crie soluções improvisadas apenas para "fazer funcionar".
8. Não remova funcionalidades existentes sem justificativa.
9. Não altere regras de negócio sem informar.
10. Mantenha o sistema funcional ao final de cada etapa.
11. Evite duplicação de código.
12. Priorize segurança e integridade dos dados.
13. Sempre valide permissões no servidor.
14. Nunca confie em IDs enviados pelo cliente.
15. Nunca exponha credenciais.
16. Nunca armazene senhas em texto puro.

Ao terminar cada task, informe:

- O que foi implementado.
- Arquivos criados/modificados.
- Banco alterado, se houver.
- Testes realizados.
- Problemas encontrados.
- Próxima task.

Não avance para a próxima task se a atual estiver quebrada.

Comece agora pela TASK 01.