<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .credentials { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { font-size: 0.8em; color: #777; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recupero Credenziali</h2>
        <p>Ciao,</p>
        <p>Abbiamo provveduto a resettare la tua password come richiesto. Di seguito trovi i tuoi nuovi dati di accesso:</p>
        
        <div class="credentials">
            <strong>Email:</strong> {{ $email }}<br>
            <strong>Nuova Password:</strong> <code>{{ $password }}</code>
        </div>

        <p>Ti consigliamo di accedere immediatamente e modificare questa password temporanea dalle impostazioni del tuo profilo.</p>
        
        <p>Grazie,<br>PokémonCollection</p>

        <div class="footer">
            Se non hai richiesto tu questa operazione, contatta subito il supporto.
        </div>
    </div>
</body>
</html>
