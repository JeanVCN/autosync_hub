# AutoSync Integration Hub Go

This folder is reserved for the future Go integration hub.

Planned responsibilities:

- Receive synchronization requests from Laravel.
- Normalize vehicle payloads for provider adapters.
- Process providers such as OLX, Mercado Livre, and iCarros.
- Handle retries, provider errors, and external references.
- Send callbacks to Laravel with final status updates.

The current Laravel phase uses a simulated `IntegrationHubClient` so the product flow can be demonstrated before implementing the Go service.
