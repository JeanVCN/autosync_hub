package sync

import (
	"context"
	"testing"
)

type fakeProvider struct{}

func (fakeProvider) Process(ctx context.Context, request SyncRequest, provider string) ProviderResult {
	_ = ctx
	ref := "REF-" + provider
	return ProviderResult{
		Provider:          provider,
		Operation:         request.Operation,
		Status:            "published",
		ExternalReference: &ref,
		ResponsePayload: map[string]any{
			"message": "ok",
		},
	}
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
	service := NewService(fakeProvider{}, dispatcher)

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
	service := NewService(fakeProvider{}, nil)
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
