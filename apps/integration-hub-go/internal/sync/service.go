package sync

import (
	"context"
	"errors"
	"fmt"
	"slices"
	"time"
)

var (
	ErrInvalidRequest      = errors.New("invalid sync request")
	ErrProviderUnavailable = errors.New("provider adapter unavailable")
)

type ProviderAdapter interface {
	Name() string
	Process(ctx context.Context, request SyncRequest) (ProviderResult, error)
}

type CallbackDispatcher interface {
	Dispatch(ctx context.Context, request SyncRequest, result ProviderResult) error
}

type Service struct {
	adapters   map[string]ProviderAdapter
	dispatcher CallbackDispatcher
	retry      RetryPolicy
}

type RetryPolicy struct {
	MaxAttempts int
	Backoff     time.Duration
}

func NewService(adapters []ProviderAdapter, dispatcher CallbackDispatcher) *Service {
	return NewServiceWithRetry(adapters, dispatcher, RetryPolicy{MaxAttempts: 1})
}

func NewServiceWithRetry(adapters []ProviderAdapter, dispatcher CallbackDispatcher, retry RetryPolicy) *Service {
	return &Service{
		adapters:   indexAdapters(adapters),
		dispatcher: dispatcher,
		retry:      normalizeRetryPolicy(retry),
	}
}

func (s *Service) Handle(ctx context.Context, request SyncRequest) (SyncResponse, error) {
	if validationErrors := s.validateRequest(request); len(validationErrors) > 0 {
		return SyncResponse{
			RequestID: request.RequestID,
			Status:    "rejected",
			Message:   "Request contains invalid fields",
			Errors:    validationErrors,
		}, ErrInvalidRequest
	}

	results := make([]ProviderResult, 0, len(request.Providers))
	for _, providerName := range request.Providers {
		result := s.processProvider(ctx, request, providerName)
		results = append(results, result)

		if request.CallbackURL != "" && s.dispatcher != nil {
			go func(result ProviderResult) {
				_ = s.dispatcher.Dispatch(context.Background(), request, result)
			}(result)
		}
	}

	return SyncResponse{
		RequestID:         request.RequestID,
		Status:            "accepted",
		Message:           "Sync request accepted for processing",
		AcceptedProviders: request.Providers,
		Results:           results,
	}, nil
}

func (s *Service) processProvider(ctx context.Context, request SyncRequest, providerName string) ProviderResult {
	adapter, ok := s.adapters[providerName]
	if !ok {
		return ProviderResult{
			Provider:          providerName,
			Operation:         request.Operation,
			Status:            "failed",
			ExternalReference: nil,
			ErrorMessage:      ErrProviderUnavailable.Error(),
			ResponsePayload: map[string]any{
				"message": ErrProviderUnavailable.Error(),
			},
		}
	}

	var lastErr error
	for attempt := 1; attempt <= s.retry.MaxAttempts; attempt++ {
		if attempt > 1 && s.retry.Backoff > 0 {
			if err := wait(ctx, s.retry.Backoff); err != nil {
				lastErr = err
				break
			}
		}

		result, err := adapter.Process(ctx, request)
		if err == nil {
			if result.ResponsePayload == nil {
				result.ResponsePayload = map[string]any{}
			}
			result.ResponsePayload["attempts"] = attempt

			return result
		}

		lastErr = err
	}

	return ProviderResult{
		Provider:          providerName,
		Operation:         request.Operation,
		Status:            "failed",
		ExternalReference: nil,
		ErrorMessage:      lastErr.Error(),
		ResponsePayload: map[string]any{
			"message":  lastErr.Error(),
			"attempts": s.retry.MaxAttempts,
		},
	}
}

func indexAdapters(adapters []ProviderAdapter) map[string]ProviderAdapter {
	indexed := make(map[string]ProviderAdapter, len(adapters))
	for _, adapter := range adapters {
		if adapter == nil || adapter.Name() == "" {
			continue
		}
		indexed[adapter.Name()] = adapter
	}

	return indexed
}

func normalizeRetryPolicy(retry RetryPolicy) RetryPolicy {
	if retry.MaxAttempts <= 0 {
		retry.MaxAttempts = 1
	}

	if retry.Backoff < 0 {
		retry.Backoff = 0
	}

	return retry
}

func wait(ctx context.Context, delay time.Duration) error {
	timer := time.NewTimer(delay)
	defer timer.Stop()

	select {
	case <-ctx.Done():
		return ctx.Err()
	case <-timer.C:
		return nil
	}
}

func (s *Service) validateRequest(request SyncRequest) map[string][]string {
	errs := map[string][]string{}

	requiredString(errs, "contract_version", request.ContractVersion)
	requiredString(errs, "request_id", request.RequestID)
	requiredString(errs, "idempotency_key", request.IdempotencyKey)
	requiredString(errs, "callback_url", request.CallbackURL)
	requiredString(errs, "operation", request.Operation)
	requiredString(errs, "vehicle.external_code", request.Vehicle.ExternalCode)
	requiredString(errs, "vehicle.brand", request.Vehicle.Brand)
	requiredString(errs, "vehicle.model", request.Vehicle.Model)
	requiredString(errs, "vehicle.status", request.Vehicle.Status)

	if !slices.Contains([]string{"publish", "update", "delete", "status_check"}, request.Operation) {
		errs["operation"] = append(errs["operation"], fmt.Sprintf("%s is not supported", request.Operation))
	}

	if request.Vehicle.Year == 0 {
		errs["vehicle.year"] = append(errs["vehicle.year"], "year is required")
	}

	if request.Vehicle.ModelYear == 0 {
		errs["vehicle.model_year"] = append(errs["vehicle.model_year"], "model_year is required")
	}

	if request.Vehicle.Price <= 0 {
		errs["vehicle.price"] = append(errs["vehicle.price"], "price must be greater than zero")
	}

	if len(request.Providers) == 0 {
		errs["providers"] = append(errs["providers"], "at least one provider is required")
	}

	for _, provider := range request.Providers {
		if !slices.Contains([]string{"olx", "mercado_livre", "icarros"}, provider) {
			errs["providers"] = append(errs["providers"], fmt.Sprintf("%s is not supported", provider))
			continue
		}

		if _, ok := s.adapters[provider]; !ok {
			errs["providers"] = append(errs["providers"], fmt.Sprintf("%s adapter is not configured", provider))
		}
	}

	if len(errs) == 0 {
		return nil
	}

	return errs
}

func requiredString(errs map[string][]string, field string, value string) {
	if value == "" {
		errs[field] = append(errs[field], field+" is required")
	}
}
