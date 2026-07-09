# Guia de Apresentação

## Ordem Sugerida

1. Comece pelo problema: lojas e concessionárias precisam de uma fonte única de estoque que consiga publicar veículos em vários marketplaces.
2. Mostre a estrutura de monorepo e explique por que Laravel e Go estão no mesmo projeto.
3. Abra a listagem de veículos do Laravel em `/vehicles`.
4. Abra a página de detalhe de um veículo e mostre o resumo atual por provider.
5. Mostre o histórico de integrações abaixo do resumo.
6. Acione `POST /api/vehicles/{vehicle}/sync`.
7. Mostre os novos logs e explique os status por provider.
8. Envie um payload de callback para `POST /api/integration-callbacks`.
9. Feche mostrando `docs/ARCHITECTURE.md`, `docs/LARAVEL_GO_CONTRACT.md` e o plano do futuro hub Go.

## Decisões Principais

- Laravel é o backend principal porque a vaga avalia Laravel e porque ele é forte para APIs, validação, migrations, persistência e telas simples.
- Go foi planejado para o hub de integração porque adapters de providers, retries e chamadas externas combinam bem com um serviço focado.
- APIs reais de marketplaces estão fora do escopo inicial porque credenciais, aprovações e ambientes de homologação desviariam o foco da arquitetura.
- O modelo de veículo é canônico para evitar que regras específicas de OLX, Mercado Livre ou iCarros vazem para o domínio principal.
- Logs de integração são persistidos porque produtos de integração precisam de rastreabilidade, suporte e visibilidade operacional.
- O contrato Laravel-Go foi documentado antes da implementação Go para evitar acoplamento improvisado entre serviços.

## Como Explicar o Papel do Laravel

Laravel concentra a parte de negócio e apresentação do produto:

- CRUD de veículos.
- Validação de entrada.
- Modelo de banco.
- Contrato da API.
- Visibilidade web.
- Histórico de status de integração.
- Endpoint de callback para atualizações futuras vindas do Go.

O código está dividido em controllers, form requests, resources, models e services para que cada camada tenha uma responsabilidade clara.

## Como Explicar o Futuro Go

O hub Go será a camada de execução de providers.

Ele deverá receber um payload canônico de veículo vindo do Laravel, mapear esse payload para cada provider, lidar com retries e erros específicos, e chamar o Laravel de volta quando o status final mudar.

## Perguntas Que o Avaliador Pode Fazer

**Por que não integrar OLX ou Mercado Livre de verdade agora?**

Porque esta fase é sobre arquitetura, contrato e fluxo demonstrável. Integrações reais normalmente exigem credenciais, aprovação, OAuth e ambientes de teste específicos por provider.

**Por que manter logs de integração em vez de só o status atual?**

Logs preservam histórico operacional, facilitam suporte e mostram por que um veículo ficou bloqueado, falhou ou exige ação.

**Por que o client simulado do hub está no Laravel?**

Porque isso mantém o produto demonstrável de ponta a ponta, mas preserva um ponto claro de substituição para o futuro serviço Go: `IntegrationHubClient`.

**O que mudaria em produção?**

Entrariam autenticação, autorização, filas/background jobs, retries reais, assinatura de webhooks, observabilidade e chamadas HTTP reais para o hub Go.

## Limitações Atuais

- Ainda não há autenticação de usuário.
- Ainda não há chamadas reais para providers.
- Ainda não há workers de fila.
- Ainda não há validação de assinatura/token no callback.
- Docker está preparado para desenvolvimento/validação, não como setup final de produção.
- O hub Go ainda não foi implementado.

## Melhorias Futuras

- Implementar o hub de integração em Go.
- Trocar a simulação do Laravel por chamada HTTP real para o Go.
- Adicionar jobs de sincronização em fila.
- Criar contratos/adapters de providers no Go.
- Adicionar validação de assinatura/token nos callbacks.
- Adicionar autenticação administrativa.
- Adicionar observabilidade para falhas de integração e contagem de retries.
