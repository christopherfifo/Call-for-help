## Nova task

Implemente as funcionalidades abaixo no sistema, **seguindo a arquitetura, padrões, componentes, banco e convenções já existentes**. Antes de codar, analise o código relacionado e reutilize o que já existe. Não crie estruturas duplicadas sem necessidade.

### 1. Chamados informais pelo técnico

Criar um tipo de chamado que o técnico pode cadastrar posteriormente quando uma solicitação foi feita informalmente.

Regras:

* Deve possuir as mesmas informações do chamado normal.
* Adicionar o campo **"solicitante"**, selecionado a partir dos usuários cadastrados no sistema.
* Identificar visualmente esse chamado com uma **tag específica**.
* O técnico deve conseguir visualizar e gerenciar esses chamados no painel.
* Adicionar um **dashboard/seção específica** no painel do técnico para chamados informais.
* Alterações no chamado devem gerar histórico contendo:

  * alteração realizada;
  * usuário responsável;
  * data/hora;
  * IP.
* A solicitação de exclusão/cancelamento não deve excluir diretamente:

  * registrar um pedido para os administradores;
  * notificar os administradores;
  * somente após autorização de um administrador o chamado poderá ser removido/cancelado.
* Registrar auditoria de criação, edição, movimentação, cancelamento/exclusão e autorizações.

### 2. Solicitação de reset de senha

Permitir que o usuário solicite reset da própria senha.

Fluxo:

1. Usuário acessa **"Esqueci minha senha"**.
2. Informa:

   * matrícula;
   * nome;
   * justificativa.
3. O sistema cria um pedido interno para os administradores.
4. Administradores recebem uma notificação no sistema.
5. Um administrador autoriza ou recusa.
6. Se autorizado, a senha do usuário volta para a **senha provisória = matrícula**.
7. Registrar todo o processo:

   * solicitante;
   * administrador responsável pela decisão;
   * data/hora;
   * IP;
   * justificativa;
   * decisão;
   * alterações realizadas.

Não permitir reset automático sem autorização administrativa.

### 3. Auditoria e IP

Para todas as operações relevantes relacionadas a chamados e pedidos, registrar:

* usuário responsável;
* ação realizada;
* data/hora;
* IP;
* entidade afetada;
* informações necessárias para identificar o que foi alterado.

Isso deve abranger, no mínimo:

* criação;
* edição;
* movimentação/status;
* cancelamento;
* exclusão/autorização;
* solicitações administrativas;
* decisões dos administradores;
* reset de senha.

Preferir uma estrutura de auditoria/histórico reutilizável em vez de implementar logs isolados para cada funcionalidade.

### 4. Notificações internas

Implementar notificações dentro do próprio sistema, na área de trabalho/painel do usuário.

Notificar quando houver:

* atualização relevante em chamado do usuário;
* movimentação/status relevante;
* pedido que dependa de ação do administrador;
* solicitação de reset de senha;
* pedido de cancelamento/exclusão de chamado;
* decisão de administrador sobre uma solicitação.

As notificações devem indicar:

* tipo;
* mensagem;
* data/hora;
* status de lida/não lida;
* usuário destinatário;
* referência à entidade relacionada quando aplicável.

### 5. Central de pedidos do administrador

Criar no painel administrativo uma área para **pedidos e notificações administrativas internas**.

Deve permitir:

* visualizar pedidos pendentes;
* identificar quem solicitou;
* visualizar justificativa/detalhes;
* visualizar data/hora e IP;
* aprovar ou recusar;
* registrar a decisão no histórico/auditoria.

Inicialmente incluir:

* reset de senha;
* cancelamento/exclusão de chamado.

Estruturar de forma que novos tipos de pedidos possam ser adicionados posteriormente.

### Requisitos gerais

* Manter compatibilidade com as funcionalidades existentes.
* Reutilizar componentes, serviços, validações e padrões já existentes.
* Aplicar separação de responsabilidades.
* Evitar duplicação de código.
* Validar permissões no backend, não apenas na interface.
* Garantir que cada ação seja associada ao usuário autenticado.
* Não permitir que um usuário altere ou aprove solicitações sem a permissão adequada.
* Manter consistência transacional nas operações que alteram múltiplas entidades.
* Atualizar banco, schemas, validações, APIs/actions, interfaces e componentes necessários.
* Criar/ajustar migrations.
* Não remover funcionalidades existentes.
* Não inventar dados ou estruturas que já possam ser obtidos do sistema.
* Antes de implementar, identifique os arquivos e entidades existentes que devem ser alterados.
* Ao final, execute os testes/validações disponíveis e corrija os erros encontrados.

### Importante

**Não fique apenas descrevendo a implementação. Execute a task no código.**

Antes de finalizar, verifique:

1. permissões;
2. auditoria;
3. registro de IP;
4. notificações;
5. histórico;
6. aprovação administrativa;
7. persistência no banco;
8. validações;
9. impacto nas funcionalidades existentes.
