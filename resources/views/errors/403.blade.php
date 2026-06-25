@extends('layouts.app')

@section('content')
<style>
    .wrap-403 {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    .card-403 {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 2.5rem 2rem;
        max-width: 480px;
        width: 100%;
        position: relative;
        overflow: hidden;
    }

    .stripe-403 {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: repeating-linear-gradient(90deg, #e24b4a 0, #e24b4a 20px, #f09595 20px, #f09595 40px);
    }

    .top-row-403 {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .pill-403 {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 500;
        background: #fcebeb;
        color: #a32d2d;
        border-radius: 999px;
        padding: 4px 12px;
    }

    .pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #e24b4a;
        animation: pulse403 1.4s ease-in-out infinite;
    }

    @keyframes pulse403 {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: 0.3
        }
    }

    .code-403 {
        font-size: 80px;
        font-weight: 500;
        line-height: 1;
        margin: 0 0 0.25rem;
        letter-spacing: -3px;
    }

    .shake {
        display: inline-block;
        animation: shake403 3s ease-in-out infinite;
    }

    @keyframes shake403 {

        0%,
        90%,
        100% {
            transform: translateX(0)
        }

        92% {
            transform: translateX(-5px)
        }

        94% {
            transform: translateX(5px)
        }

        96% {
            transform: translateX(-4px)
        }

        98% {
            transform: translateX(4px)
        }
    }

    .checklist-403 {
        list-style: none;
        padding: 0;
        margin: 0 0 1.75rem;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .checklist-403 li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        color: #6b7280;
    }

    .check-ico {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #fcebeb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
        font-size: 12px;
        color: #a32d2d;
    }
</style>

<div class="wrap-403">
    <div class="card-403">
        <div class="stripe-403"></div>
        <div class="top-row-403">
            <div class="pill-403">
                <div class="pill-dot"></div> Access denied
            </div>
            <span class="shake" style="font-size:52px">🚷</span>
        </div>
        <p class="code-403">403</p>
        <h3 style="margin:0 0 0.5rem">You shall not pass.</h3>
        <p style="font-size:14px; color:#6b7280; margin:0 0 1.5rem; line-height:1.7">
            Gandalf himself reviewed your permissions and respectfully declined.
            This page is locked tighter than my ex's Instagram.
        </p>
        <hr style="border:none; border-top:1px solid #e5e7eb; margin:0 0 1.5rem">
        <ul class="checklist-403">
            <li>
                <div class="check-ico">✕</div><span>You're not logged in as the right person — try switching accounts.</span>
            </li>
            <li>
                <div class="check-ico">✕</div><span>Your account doesn't have the required permission level.</span>
            </li>
            <li>
                <div class="check-ico">✕</div><span>The page exists — you're just not on the guest list.</span>
            </li>
        </ul>
        <div style="display:flex; gap:8px">
            <a href="{{ url('/') }}" class="btn btn-outline-secondary" style="flex:1; text-align:center">Home</a>
        </div>
    </div>
</div>
@endsection