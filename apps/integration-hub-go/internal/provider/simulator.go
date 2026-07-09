package provider

import (
	"context"
	"fmt"
	"strings"

	autosyncsync "autosync-hub/integration-hub-go/internal/sync"
)

type Simulator struct{}

func NewSimulator() Simulator {
	return Simulator{}
}

func (s Simulator) Process(ctx context.Context, request autosyncsync.SyncRequest, provider string) autosyncsync.ProviderResult {
	_ = ctx

	if provider == "icarros" && strings.TrimSpace(request.Vehicle.Version) == "" {
		return autosyncsync.ProviderResult{
			Provider:          provider,
			Operation:         request.Operation,
			Status:            "failed",
			ExternalReference: nil,
			ErrorMessage:      "Version field is required by provider",
			ResponsePayload: map[string]any{
				"provider_status_code": "422",
				"provider_error_code":  "missing_version",
			},
		}
	}

	status := "published"
	if provider == "mercado_livre" {
		status = "processing"
	}

	reference := externalReference(provider, request.Vehicle.ExternalCode)

	return autosyncsync.ProviderResult{
		Provider:          provider,
		Operation:         request.Operation,
		Status:            status,
		ExternalReference: &reference,
		ResponsePayload: map[string]any{
			"message":              "Simulated provider response",
			"provider_status_code": "200",
		},
	}
}

func externalReference(provider string, externalCode string) string {
	prefix := strings.ToUpper(strings.ReplaceAll(provider, "_", ""))
	return fmt.Sprintf("%s-%s", prefix, externalCode)
}
