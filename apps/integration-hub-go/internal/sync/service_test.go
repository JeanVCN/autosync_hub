package sync

import (
	"context"
	"errors"
	"testing"
)

type fakeProvider struct{}

func (fakeProvider) Name() string {
	return "olx"
}

func (fakeProvider) Process(ctx context.Context, request SyncRequest) (ProviderResult, error) {
	_ = ctx
	provider := "olx"
	ref := "REF-" + provider
	return ProviderResult{
		Provider:          provider,
		Operation:         request.Operation,
		Status:            "published",
		ExternalReference: &ref,
		ResponsePayload: map[string]any{
			"message": "ok",
		},
	}, nil
}

type recordingDispatcher struct {
	calls int
}

func (d *recordingDispatcher) Dispatch(ctx context.Context, request SyncRequest, result ProviderResult) error {
	_ = ctx
	_ = request
	_ = result
	d.calls++
	return nil
}

func TestServiceAcceptsValidSyncRequest(t *testing.T) {
	dispatcher := &recordingDispatcher{}
	service := NewService([]ProviderAdapter{
		fakeProvider{},
		fakeMercadoLivreProvider{},
	}, dispatcher)

	response, err := service.Handle(context.Background(), validRequest())
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if response.Status != "accepted" {
		t.Fatalf("expected accepted status, got %s", response.Status)
	}

	if len(response.Results) != 2 {
		t.Fatalf("expected 2 results, got %d", len(response.Results))
	}

	if dispatcher.calls != 2 {
		t.Fatalf("expected 2 callback dispatches, got %d", dispatcher.calls)
	}
}

func TestServiceRejectsInvalidProvider(t *testing.T) {
	service := NewService([]ProviderAdapter{fakeProvider{}}, nil)
	request := validRequest()
	request.Providers = []string{"unknown_provider"}

	response, err := service.Handle(context.Background(), request)
	if err != ErrInvalidRequest {
		t.Fatalf("expected ErrInvalidRequest, got %v", err)
	}

	if response.Status != "rejected" {
		t.Fatalf("expected rejected status, got %s", response.Status)
	}

	if len(response.Errors["providers"]) == 0 {
		t.Fatal("expected provider validation error")
	}
}

func TestServiceRejectsSupportedProviderWithoutConfiguredAdapter(t *testing.T) {
	service := NewService([]ProviderAdapter{fakeProvider{}}, nil)
	request := validRequest()
	request.Providers = []string{"icarros"}

	response, err := service.Handle(context.Background(), request)
	if err != ErrInvalidRequest {
		t.Fatalf("expected ErrInvalidRequest, got %v", err)
	}

	if len(response.Errors["providers"]) == 0 {
		t.Fatal("expected provider adapter validation error")
	}
}

func TestServiceRetriesProviderAdapterErrors(t *testing.T) {
	provider := &flakyProvider{}
	service := NewServiceWithRetry([]ProviderAdapter{provider}, nil, RetryPolicy{MaxAttempts: 2})
	request := validRequest()
	request.Providers = []string{"olx"}

	response, err := service.Handle(context.Background(), request)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if provider.calls != 2 {
		t.Fatalf("expected 2 provider calls, got %d", provider.calls)
	}

	if response.Results[0].Status != "published" {
		t.Fatalf("expected published status, got %s", response.Results[0].Status)
	}

	if response.Results[0].ResponsePayload["attempts"] != 2 {
		t.Fatalf("expected attempts metadata to be 2, got %#v", response.Results[0].ResponsePayload["attempts"])
	}
}

func TestServiceReturnsFailedResultAfterRetryExhaustion(t *testing.T) {
	service := NewServiceWithRetry([]ProviderAdapter{alwaysFailProvider{}}, nil, RetryPolicy{MaxAttempts: 2})
	request := validRequest()
	request.Providers = []string{"olx"}

	response, err := service.Handle(context.Background(), request)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	result := response.Results[0]
	if result.Status != "failed" {
		t.Fatalf("expected failed status, got %s", result.Status)
	}

	if result.ResponsePayload["attempts"] != 2 {
		t.Fatalf("expected attempts metadata to be 2, got %#v", result.ResponsePayload["attempts"])
	}
}

type fakeMercadoLivreProvider struct{}

func (fakeMercadoLivreProvider) Name() string {
	return "mercado_livre"
}

func (fakeMercadoLivreProvider) Process(ctx context.Context, request SyncRequest) (ProviderResult, error) {
	_ = ctx
	ref := "REF-mercado_livre"
	return ProviderResult{
		Provider:          "mercado_livre",
		Operation:         request.Operation,
		Status:            "processing",
		ExternalReference: &ref,
		ResponsePayload: map[string]any{
			"message": "ok",
		},
	}, nil
}

type flakyProvider struct {
	calls int
}

func (p *flakyProvider) Name() string {
	return "olx"
}

func (p *flakyProvider) Process(ctx context.Context, request SyncRequest) (ProviderResult, error) {
	_ = ctx
	p.calls++
	if p.calls == 1 {
		return ProviderResult{}, errors.New("temporary provider outage")
	}

	ref := "REF-olx"
	return ProviderResult{
		Provider:          "olx",
		Operation:         request.Operation,
		Status:            "published",
		ExternalReference: &ref,
		ResponsePayload: map[string]any{
			"message": "ok",
		},
	}, nil
}

type alwaysFailProvider struct{}

func (alwaysFailProvider) Name() string {
	return "olx"
}

func (alwaysFailProvider) Process(ctx context.Context, request SyncRequest) (ProviderResult, error) {
	_ = ctx
	_ = request
	return ProviderResult{}, errors.New("provider unavailable")
}

func validRequest() SyncRequest {
	return SyncRequest{
		ContractVersion: "2026-07-09",
		RequestID:       "request-1",
		IdempotencyKey:  "key-1",
		CallbackURL:     "http://localhost:8000/api/integration-callbacks",
		Operation:       "publish",
		Providers:       []string{"olx", "mercado_livre"},
		Vehicle: Vehicle{
			ExternalCode: "CAR-001",
			Brand:        "Honda",
			Model:        "Civic",
			Version:      "EXL 2.0",
			Year:         2020,
			ModelYear:    2021,
			Price:        118900,
			Status:       "active",
		},
	}
}
