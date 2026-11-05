<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            color: #23364d;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            margin: 0;
        }

        .header {
            background: #194d88;
            color: #fff;
            padding: 11px 35px;
            width: 100%;
        }

        .brand {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: .4px;
        }

        .brand-note {
            font-size: 8px;
            text-align: right;
        }

        .content {
            padding: 15px 38px 20px;
        }

        h1 {
            color: #244e80;
            font-size: 14px;
            margin: 0 0 3px;
        }

        .booking-meta {
            color: #64748b;
            font-size: 8px;
            margin-bottom: 9px;
        }

        .notice {
            background: #eef5fc;
            border: 1px solid #9bb9d8;
            color: #244e80;
            margin-bottom: 10px;
            padding: 7px 9px;
        }

        .notice-title {
            font-weight: bold;
            margin: 4px 0 2px;
        }

        .notice p {
            margin: 0;
        }

        .notice ul {
            margin: 2px 0 0;
            padding-left: 14px;
        }

        h2 {
            color: #244e80;
            font-size: 10px;
            margin: 9px 0 3px;
            text-transform: none;
        }

        .details,
        .project-details {
            border-collapse: collapse;
            width: 100%;
        }

        .details td,
        .project-details td {
            border-bottom: 1px solid #cbd5e1;
            padding: 3px 2px;
            vertical-align: top;
        }

        .label {
            color: #64748b;
            width: 29%;
        }

        .value {
            color: #1f2937;
            font-weight: bold;
        }

        .product {
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
            margin: 5px 0;
            page-break-inside: avoid;
            width: 100%;
        }

        .product-image {
            padding: 5px 7px;
            text-align: center;
            vertical-align: middle;
            width: 62px;
        }

        .product-image img {
            height: 72px;
            object-fit: contain;
            width: 58px;
        }

        .product-info {
            padding: 5px 8px 5px 2px;
            vertical-align: middle;
        }

        .product-name {
            color: #244e80;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .product-info p {
            margin: 1px 0;
        }

        .product-label {
            font-weight: bold;
        }

        .terms {
            color: #334155;
            font-size: 8px;
            margin-top: 9px;
        }

        .terms p {
            margin: 2px 0;
        }

        .thank-you {
            color: #244e80;
            font-weight: bold;
        }

        .contact-note {
            color: #64748b;
            font-size: 7px;
            margin-top: 4px;
        }

        .important-note {
            border: 1px solid #cbd5e1;
            color: #475569;
            font-size: 7px;
            margin-top: 6px;
            padding: 5px 7px;
        }

        .important-note strong {
            color: #244e80;
            display: block;
            margin-bottom: 2px;
        }

        .footer {
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 7px;
            margin-top: 8px;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="brand">TATA PRAVESH</td>
            <td class="brand-note">Doors &amp; Windows by Tata Steel</td>
        </tr>
    </table>

    <main class="content">
        <h1>Tata Pravesh | Your Pre-Booking Confirmation &amp; Demo Request</h1>
        <div class="booking-meta">
            Pre-Booking ID: {{ $bookingNumber }}
            &nbsp; | &nbsp;
            Submitted on: {{ $submittedAt->format('d F Y') }}
        </div>

        <div class="notice">
            <p><strong>Our executive will contact you within 24 hours to schedule your demo.</strong></p>
            <p class="notice-title">Payment &amp; Order Process:</p>
            <ul>
                <li>No payment is needed at the pre-booking stage.</li>
                <li>Final pricing, specifications, and product selection will be confirmed after discussion with your authorized Tata Pravesh dealer.</li>
                <li>Payment terms for your order will be shared by the dealer before confirmation.</li>
            </ul>
        </div>

        <h2>Your Details</h2>
        <table class="details">
            <tr>
                <td class="label">Name</td>
                <td class="value">{{ $user->name }}</td>
            </tr>
            <tr>
                <td class="label">Mobile number</td>
                <td class="value">{{ $user->phone ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <td class="label">Email ID</td>
                <td class="value">{{ $user->email }}</td>
            </tr>
        </table>

        <h2>Delivery Address</h2>
        <table class="details">
            <tr>
                <td class="label">Address</td>
                <td class="value">{{ $cartDetail->address }}</td>
            </tr>
            <tr>
                <td class="label">Pincode</td>
                <td class="value">{{ $cartDetail->pincode }}</td>
            </tr>
        </table>

        <h2>Project Details</h2>
        <table class="project-details">
            <tr>
                <td class="label">Construction stage</td>
                <td class="value">{{ $cartDetail->construction_stage }}</td>
            </tr>
            <tr>
                <td class="label">Total products ordered</td>
                <td class="value">{{ count($products) }} {{ count($products) === 1 ? 'product' : 'products' }}</td>
            </tr>
        </table>

        <h2>Products Selected</h2>
        @foreach ($products as $index => $product)
            <table class="product">
                <tr>
                    <td class="product-image">
                        @if ($product['image_data_uri'])
                            <img src="{{ $product['image_data_uri'] }}" alt="">
                        @endif
                    </td>
                    <td class="product-info">
                        <div class="product-name">{{ $index + 1 }}. {{ $product['productName'] }}</div>
                        <p><span class="product-label">Type:</span> {{ $product['type'] }}</p>
                        <p><span class="product-label">Model:</span> {{ $product['modelName'] }}</p>
                        <p><span class="product-label">Design:</span> {{ $product['designName'] }}</p>
                        <p><span class="product-label">Shade:</span> {{ $product['shadeName'] }}</p>
                        <p><span class="product-label">Size:</span> {{ $product['sizeLabel'] }}</p>
                        <p><span class="product-label">Quantity:</span> {{ $product['quantity'] }}</p>
                    </td>
                </tr>
            </table>
        @endforeach

        <div class="terms">
            <h2>Terms &amp; Conditions</h2>
            <p>Accepted at the time of submission.</p>
            <p class="thank-you">Thank you for choosing Tata Pravesh. Our executive will contact you within 24 hours.</p>
            <p class="contact-note">Helpline: 1800-419-200 | This is a booking request; no payment has been taken.</p>
            <div class="important-note">
                <strong>Important Note:</strong>
                This document confirms your pre-booking request only. It is not a final product order or price confirmation. Final product selection, specifications, accessories, pricing, and applicable services will be confirmed after discussion with an authorized Tata Pravesh dealer. Offerings and pricing may vary by location and customer requirements.
            </div>
        </div>

        <div class="footer">TATA PRAVESH &nbsp; | &nbsp; Doors &amp; Windows by Tata Steel</div>
    </main>
</body>
</html>
