package sync

import (
	"context"
	"errors"
	"fmt"
	"slices"
)

var (
	ErrInvalidRequest = errors.New("invalid sync request")
)

type Provider interface {
	Process(ctx context.Context, request SyncRequest, provider string) ProviderResult
}

type CallbackDispatcher interface {
	Dispatch(ctx context.Context, request SyncRequest, result ProviderResult) error
}

type Service struct {
	provider   Provider
	dispatcher CallbackDispatcher
}

func NewService(provider Provider, dispatcher CallbackDispatcher) *Service {
	return &Service{
		provider:   provider,
		dispatcher: dispatcher,
	}
}

func (s *Service) Handle(ctx context.Context, request SyncRequest) (SyncResponse, error) {
	if validationErrors := validateRequest(request); len(validationErrors) > 0 {
		return SyncResponse{
			RequestID: request.RequestID,
			Status:    "rejected",
			Message:   "Request contains invalid fields",
			Errors:    validationErrors,
		}, ErrInvalidRequest
	}

	results := make([]ProviderResult, 0, len(request.Providers))
	for _, provider := range request.Providers {
		result := s.provider.Process(ctx, request, provider)
		results = append(results, result)

		if request.CallbackURL != "" && s.dispatcher != nil {
			_ = s.dispatcher.Dispatch(ctx, request, result)
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

func validateRequest(request SyncRequest) map[string][]string {
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
