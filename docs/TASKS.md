# SISTEMA DE CHAMADOS - TASKS

## TASK 01 — ANÁLISE DO PROJETO
- [x] Analisar estrutura existente.
- [x] Identificar tecnologias.
- [x] Identificar banco.
- [x] Identificar arquivos existentes.
- [x] Criar arquitetura planejada.
- [x] Criar TASKS.md.

## TASK 02 — BANCO DE DADOS
- [x] Criar estrutura MySQL.
- [x] Criar tabelas.
- [x] Criar foreign keys.
- [x] Criar índices.
- [x] Criar usuário inicial DEV/Superadmin.
- [x] Criar categorias iniciais.
- [x] Criar script SQL de instalação.
- [x] Testar relacionamentos.

## TASK 03 — AUTENTICAÇÃO
- [x] Login.
- [x] Logout.
- [x] Sessões.
- [x] Password hash.
- [x] Password verify.
- [x] Primeiro acesso.
- [x] Troca obrigatória de senha.
- [x] Proteção das páginas.
- [x] Controle de perfil.

## TASK 04 — ESTRUTURA DA INTERFACE
- [x] Layout Bootstrap.
- [x] Sidebar.
- [x] Navbar.
- [x] Responsividade.
- [x] Componentes reutilizáveis.
- [x] Alertas.
- [x] Modais.
- [x] Badges.
- [x] Tabelas.
- [x] Paginação.

## TASK 05 — USUÁRIOS
- [x] Cadastro.
- [x] Edição.
- [x] Desativação.
- [x] Reativação.
- [x] Alteração de cargo.
- [x] Reset de senha.
- [x] Primeiro acesso.

## TASK 06 — ADMINISTRADORES
- [x] Área DEV.
- [x] Cadastro de administradores.
- [x] Edição.
- [x] Desativação.
- [x] Reativação.
- [x] Reset de senha.
- [x] Controle de níveis.
- [x] Logs administrativos.

## TASK 07 — CATEGORIAS
- [x] CRUD de categorias.
- [x] Ativação/desativação.
- [x] Validação.

## TASK 08 — ABERTURA DE CHAMADOS
- [x] Formulário.
- [x] Categoria.
- [x] Assunto.
- [x] Descrição.
- [x] Urgência.
- [x] Justificativa.
- [x] Número automático.
- [x] Status inicial ABERTO.
- [x] Histórico inicial.

## TASK 09 — VISUALIZAÇÃO DOS CHAMADOS
- [x] Lista.
- [x] Detalhes.
- [x] Filtros.
- [x] Pesquisa.
- [x] Ordenação por urgência.
- [x] Ordenação por antiguidade.
- [x] Paginação.
- [x] Badges.
- [x] Controle de acesso.

## TASK 10 — FILA DO TÉCNICO
- [x] Fila.
- [x] Assumir chamado.
- [x] Definir prazo.
- [x] Prazo indeterminado.
- [x] Justificativa.
- [x] Histórico.

## TASK 11 — PAUSA E RETOMADA
- [x] Pausar.
- [x] Justificativa.
- [x] Retomar.
- [x] Histórico.

## TASK 12 — REPASSE
- [x] Repassar para técnico.
- [x] Devolver para fila.
- [x] Tag REPASSADO.
- [x] Justificativa.
- [x] Histórico.

## TASK 13 — FINALIZAÇÃO
- [x] Relatório final obrigatório.
- [x] Finalização.
- [x] Data/hora.
- [x] Histórico.
- [x] Bloqueio de finalização sem relatório.

## TASK 14 — CANCELAMENTO
- [x] Cancelamento pelo usuário.
- [x] Cancelamento pelo administrador.
- [x] Justificativa obrigatória.
- [x] Registro de quem cancelou.
- [x] Histórico.
- [x] Bloqueio para técnico.

## TASK 15 — REABERTURA
- [x] Reabertura pelo administrador.
- [x] Justificativa.
- [x] Tag REABERTO.
- [x] Histórico.
- [x] Retorno para atendimento.

## TASK 16 — ALTERAÇÃO DE URGÊNCIA
- [x] Alteração pelo administrador.
- [x] Justificativa obrigatória.
- [x] Histórico.
- [x] Registro anterior/novo valor.

## TASK 17 — ATUALIZAÇÕES
- [x] Usuário adicionar atualização.
- [x] Técnico adicionar atualização.
- [x] Histórico.
- [x] Preservar descrição original.

## TASK 18 — ANEXOS
- [x] Upload.
- [x] Validação.
- [x] Download.
- [x] Controle de acesso.
- [x] Histórico.

## TASK 19 — SOLICITAÇÃO DE RELATÓRIO
- [x] Usuário solicitar.
- [x] Técnico solicitar.
- [x] Motivo obrigatório.
- [x] Tela administrativa.
- [x] Status da solicitação.
- [x] Atendimento.
- [x] Histórico.

## TASK 20 — RELATÓRIO PDF
- [x] Layout profissional.
- [x] Dados do chamado.
- [x] Histórico.
- [x] Atualizações.
- [x] Relatório final.
- [x] Dados de cancelamento.
- [x] Dados de reabertura.
- [x] Dados de repasse.
- [x] Geração.
- [x] Download.

## TASK 21 — DASHBOARDS
- [x] Dashboard usuário.
- [x] Dashboard técnico.
- [x] Dashboard administrador.
- [x] Dashboard DEV.
- [x] Indicadores.
- [x] Chamados prioritários.
- [x] Chamados atrasados.

## TASK 22 — SLA
- [x] Controle de prazo.
- [x] Dentro do prazo.
- [x] Próximo do vencimento.
- [x] Vencido.
- [x] Prazo indeterminado.

## TASK 23 — AUDITORIA
- [x] Histórico completo.
- [x] Logs do sistema.
- [x] Logs administrativos.
- [x] Registro de IP quando apropriado.
- [x] Impossibilidade de alteração por usuários comuns.

## TASK 24 — SEGURANÇA
- [x] SQL Injection.
- [x] XSS.
- [x] CSRF.
- [x] Session hijacking.
- [x] Controle de acesso.
- [x] Upload.
- [x] Senhas.
- [x] Acesso direto às páginas.
- [x] IDOR.
- [x] Validação de entrada.

## TASK 25 — TESTES
- [x] Login.
- [x] Usuário.
- [x] Técnico.
- [x] Administrador.
- [x] DEV.
- [x] Chamado.
- [x] Permissões.
- [x] Cancelamento.
- [x] Reabertura.
- [x] Repasse.
- [x] Finalização.
- [x] Relatório.
- [x] PDF.
- [x] Responsividade.

## TASK 26 — REVISÃO FINAL
- [x] Revisar código.
- [x] Remover código morto.
- [x] Corrigir erros.
- [x] Melhorar organização.
- [x] Verificar segurança.
- [x] Verificar responsividade.
- [x] Verificar banco.
- [x] Verificar integridade dos dados.
- [x] Verificar todas as permissões.
- [x] Atualizar documentação.
