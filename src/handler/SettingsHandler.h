#ifndef FP_SETTINGSHANDLER_H_
#define FP_SETTINGSHANDLER_H_

class SettingsHandler : public Handler
{
public:
    void OnRequest(HttpRequest& request, HttpResponse& response) override;

protected:
    void OnSetFreePanel(HttpRequest& request, HttpResponse& response);

};

#endif

