package main

import (
	"log"
	"net/http"

	"autosync-hub/integration-hub-go/internal/callback"
	"autosync-hub/integration-hub-go/internal/config"
	"autosync-hub/integration-hub-go/internal/httpapi"
	"autosync-hub/integration-hub-go/internal/provider"
	autosyncsync "autosync-hub/integration-hub-go/internal/sync"
)

func main() {
	cfg := config.FromEnv()

	callbackDispatcher := callback.NewDispatcher(cfg.CallbackTimeout, cfg.ContractVersion, cfg.ServiceToken)
	syncService := autosyncsync.NewServiceWithRetry(provider.NewSimulatedAdapters(), callbackDispatcher, autosyncsync.RetryPolicy{
		MaxAttempts: cfg.ProviderMaxAttempts,
		Backoff:     cfg.ProviderBackoff,
	})
	server := httpapi.NewServer(cfg, syncService)

	log.Printf("AutoSync Integration Hub listening on %s", cfg.Addr)
	if err := http.ListenAndServe(cfg.Addr, server.Handler()); err != nil {
		log.Fatal(err)
	}
}
