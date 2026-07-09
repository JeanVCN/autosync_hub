package callback

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	autosyncsync "autosync-hub/integration-hub-go/internal/sync"
)

type Dispatcher struct {
	client          *http.Client
	contractVersion string
	serviceToken    string
}

func NewDispatcher(timeout time.Duration, contractVersion string, serviceToken string) Dispatcher {
	return Dispatcher{
		client: &http.Client{
			Timeout: timeout,
		},
		contractVersion: contractVersion,
		serviceToken:    serviceToken,
	}
}

func (d Dispatcher) Dispatch(ctx context.Context, request autosyncsync.SyncRequest, result autosyncsync.ProviderResult) error {
	payload := autosyncsync.CallbackPayload{
		VehicleExternalCode: request.Vehicle.ExternalCode,
		Provider:            result.Provider,
		Operation:           result.Operation,
		Status:              result.Status,
		ExternalReference:   result.ExternalReference,
		ErrorMessage:        result.ErrorMessage,
		ResponsePayload:     result.ResponsePayload,
	}

	body, err := json.Marshal(payload)
	if err != nil {
		return fmt.Errorf("marshal callback payload: %w", err)
	}

	httpRequest, err := http.NewRequestWithContext(ctx, http.MethodPost, request.CallbackURL, bytes.NewReader(body))
	if err != nil {
		return fmt.Errorf("create callback request: %w", err)
	}

	httpRequest.Header.Set("Content-Type", "application/json")
	httpRequest.Header.Set("Accept", "application/json")
	httpRequest.Header.Set("X-Contract-Version", d.contractVersion)
	httpRequest.Header.Set("X-Request-Id", request.RequestID)
	if d.serviceToken != "" {
		httpRequest.Header.Set("Authorization", "Bearer "+d.serviceToken)
	}

	response, err := d.client.Do(httpRequest)
	if err != nil {
		return fmt.Errorf("send callback: %w", err)
	}
	defer response.Body.Close()

	if response.StatusCode < http.StatusOK || response.StatusCode >= http.StatusMultipleChoices {
		return fmt.Errorf("callback returned status %d", response.StatusCode)
	}

	return nil
}
